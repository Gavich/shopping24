<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Product_Bridge extends TC_Import_Model_Processor{

	const SKIP = 'skip';
	protected $_processCode;
	protected $_file = null;
	protected $_worker;

	/**
	 * Get prefix for logger storage name
	 * @return  string
	 */
	public function getLogPrefix(){
		return $this->_getProcessCode();
	}

	/**
	 * Getter for worker object
	 * @return TC_Import_Model_Product_Populating_Query
	 */
	public function getWorker(){
		if($this->_worker == null){
			$this->_worker = Mage::getModel('tcimport/product_populating_query')
				->setConnection($this->getAdapter())
				->setLoger($this->getLoger());
		}

		return $this->_worker;
	}

	/**
	 * Process import
	 * @return void(0)
	 */
	public function run(){
		$this->_beforeRun();

		$this->getWorker()
			->createTable($this->_getProcessCode())
			->loadCSV(TC_Import_Model_Product_Processor::getPath('WORKING') . DS . $this->_getFile());

		$this->getWorker()->addBeginTransaction();
		$this
			->InsertSqlGlobals()
			->InsertProductsIntoMagento()
			->InsertProductsTax()
			->InsertProductsInCategories()
			->InsertProductsWebsiteLink()
			->InsertProductsInventory()
			->InsertProductsAttributes()
			->InsertProductsRelations()
			// ->InsertCoreUrlRewrite()
			;

		$this->getWorker()->execute($this->_getFile());
	}

	/**
	 * Returns first filename form NEW folder
	 * @return string
	 */
	protected function _getFile(){
		if ($this->_file == null){
			$workingDir = TC_Import_Model_Product_Processor::getPath('NEW');

			$this->_file = false;
			$iterator = new DirectoryIterator($workingDir);
			foreach ($iterator as $fileinfo){
				if ($fileinfo->isFile()){
					$this->_file = $fileinfo->getFilename();
					break;
				}
			}
			if (!$this->_file){
				$this->_terminateCriticalError('Nothing to import...');
			}

			//change file location
			//NOTE: finally location changes in execute method of worker object
			TC_Import_Model_Product_Fs::markWorking($this->_getFile());
		}

		return $this->_file;
	}

	/**
	 * Set process code by filename
	 * @param TC_Import_Model_Product_Bridge
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function _setProcessCode($filename){
		$filename = str_replace('.csv', '', $filename);
		$filename = preg_replace('#[^a-z0-9]+#i', '', $filename);
		$filename = trim($filename);
		if ($filename == ''){
			$this->_terminateCriticalError('Bad filename');
		}

		if (is_numeric($filename)){
			$filename = 'aa' . $filename;
		}

		$this->_processCode = $filename;

		return $this;
	}

	/**
	 * Returns process code (filename)
	 * @return string
	 */
	protected function _getProcessCode(){
		return $this->_processCode;
	}

	/**
	 * Executed before main process run
	 * @return TC_Import_Model_Product_Bridge
	 */
	private function _beforeRun(){
		$file = $this->_getFile();
		$this->_setProcessCode($file);

		$logInstance = Mage::getModel('tcimport/loger_file')
											->setProcess($this);

		$this->setAdapter(Mage::getModel('core/resource')->getConnection('core_write'))
			->setLoger($logInstance);

		$varDir = Mage::getBaseDir('var');

		if (!is_writable($varDir)){
			$this->_terminateCriticalError('VAR dir is not writeable');
		}

		//creating required directories
		$workingDir = $varDir . DS . TC_Import_Model_Product_Processor::$_workingDir;
		foreach (TC_Import_Model_Product_Processor::$_dirs as $dir){
			$fullPath = $workingDir . DS . $dir;
			if(!is_dir($fullPath)){
				mkdir($fullPath, 0777, true);
			}
		}

		Varien_Profiler::enable();

		return $this;
	}

	/**
	 * Set all variables used for import
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertSqlGlobals(){
		$resource = Mage::getSingleton('core/resource');

		/* default_category_id */
		$comment = 'Set default category id to 2';
		$sql = 'SET @default_category_id=2;';
		$this->getWorker()->addQuery($sql, $comment);

		/* category_entity_type_id */
		$comment = ' Set category_entity_type_id to catalog_category entity type';
		$sql = 'SET @category_entity_type_id = (SELECT entity_type_id FROM ' . $resource->getTableName('eav_entity_type') . ' WHERE entity_type_code="catalog_category");';
		$this->getWorker()->addQuery($sql, $comment);

		/* product_entity_type_id */
		$comment = ' Set product_entity_type_id to catalog_product entity type';
		$sql = 'SET @product_entity_type_id = (SELECT entity_type_id 
				FROM ' . $resource->getTableName('eav_entity_type') . ' 
				WHERE entity_type_code="' . Mage_Catalog_Model_Product::ENTITY . '");';
		$this->getWorker()->addQuery($sql, $comment);

		/* store_id */
		$comment = 'Set store id to 0';
		$sql = 'SET @store_id=0;';
		$this->getWorker()->addQuery($sql, $comment);

		/* website_id */
		$comment = 'Set website_id to 1';
		$sql = 'SET @website_id=1;';
		$this->getWorker()->addQuery($sql, $comment);

		/* front_store_id */
		$comment = 'Set front_store_id to 1';
		$sql = 'SET @front_store_id=1;';
		$this->getWorker()->addQuery($sql, $comment);

		/* taxAttribute */
		$comment = 'Set taxAttribute';
		$sql = ' SET @taxAttribute = (SELECT attribute_id 
				FROM ' . $resource->getTableName('eav_attribute') . ' 
				WHERE entity_type_id=@product_entity_type_id
				AND attribute_code = "tax_class_id");';
		$this->getWorker()->addQuery($sql, $comment);

		$comment = 'Set defaultstock_id variable';
		$sql = 'SET @defaultstock_id = (SELECT stock_id 
				FROM ' . $resource->getTableName('cataloginventory_stock') . ' WHERE stock_name = "Default")';
		$this->getWorker()->addQuery($sql, $comment);

		/* stock_availability_id */
		$comment = 'Set stock_availability_id';
		$sql = 'SELECT attribute_id INTO @stock_availability_id
				FROM ' . $resource->getTableName('eav_attribute') . ' 
				WHERE entity_type_id=@product_entity_type_id
				AND attribute_code = "stock_availability";';
		$this->getWorker()->addQuery($sql, $comment);

		$multiplier = 1;
		if (Mage::getStoreConfig('tcimport_database/tcimport_import_group/multiplier') > 1){
			$multiplier = (float)Mage::getStoreConfig('tcimport_database/tcimport_import_group/multiplier');
		}

		$comment = 'Set price multiplier';
		$sql = "SET @multiplier=" . $multiplier . ";";
		$this->getWorker()->addQuery($sql, $comment);

		return $this;
	}

	/**
	 * Insert products from configured table to magento model
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertProductsIntoMagento(){
		$resource = Mage::getSingleton('core/resource');

		$this->findExistingProducts();

		$columns = $this->getWorker()->getConfig()->getColumns();

		if(array_key_exists('attribute_set', $columns) 
			&& array_key_exists('type', $columns) 
			&& array_key_exists('has_options', $columns)
			&& array_key_exists('sku', $columns)){

			$comment = 'Inserting new products';
			$sql = "INSERT INTO " . $resource->getTableName('catalog_product_entity') . " 
				(entity_type_id, attribute_set_id, type_id, sku, has_options, required_options, created_at, updated_at)
				SELECT @product_entity_type_id, 
						eas.attribute_set_id,
						b.type, 
						b.sku, 
						b.has_options, 0, NOW(), NOW()
				FROM " . $this->getWorker()->getTableName() . " b
				LEFT JOIN " . $resource->getTableName('eav_attribute_set') . " eas ON eas.attribute_set_name = b.attribute_set COLLATE utf8_swedish_ci
				WHERE eas.entity_type_id=@product_entity_type_id AND b.magento_entity_id IS NULL;";
			$this->getWorker()->addQuery($sql, $comment, 'show_results');

			$this->matchWithProductsIntoMagento();
		}

		return $this;
	}

	/**
	 * Tries to update new table with existing ids
	 * @return void(0)
	 */
	protected function findExistingProducts(){
		$resource = Mage::getSingleton('core/resource');

		$comment = 'Updating existing products';
		$sql = "
			UPDATE " . $this->getWorker()->getTableName() . " b, " . $resource->getTableName('catalog_product_entity') . " cpe
			SET b.magento_entity_id = cpe.entity_id
			WHERE b.sku = cpe.sku;
		";
		$this->getWorker()->addQuery($sql); 
		
		$sql = "UPDATE " . $this->getWorker()->getTableName() . " b, " . $resource->getTableName('catalog_product_entity') . " cpe
			SET cpe.updated_at = NOW()
			WHERE b.sku = cpe.sku;";
		$this->getWorker()->addQuery($sql); 
	}

	/**
	 * Trys to update new table with existing ids
	 * @return void(0)
	 */
	protected function matchWithProductsIntoMagento(){
		$resource = Mage::getSingleton('core/resource');

		$comment = 'Update magento id mapping in ' . $this->getWorker()->getTableName() . ' table';
		$sql = "UPDATE " . $this->getWorker()->getTableName() . " b, " . $resource->getTableName('catalog_product_entity') . " cpe
			SET b.magento_entity_id = cpe.entity_id
			WHERE b.sku = cpe.sku;
		";
		$this->getWorker()->addQuery($sql, $comment);
	}

	/**
	 * REPLACE magento tax class for products
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertProductsTax(){
		$resource = Mage::getSingleton('core/resource');
		$columns = $this->getWorker()->getConfig()->getColumns();

		if(array_key_exists('tax_class_id', $columns)){
			$comment = 'REPLACE tax class_id into Magento';
			$sql = "REPLACE INTO " . $resource->getTableName('catalog_product_entity_int') . " (entity_type_id, attribute_id, store_id, entity_id, value)
				SELECT @product_entity_type_id, @taxAttribute, @store_id, b.magento_entity_id, b.tax_class_id
				FROM " . $this->getWorker()->getTableName() . " b
				WHERE b.magento_entity_id IS NOT NULL;";
			$this->getWorker()->addQuery($sql, $comment);
		}

		return $this;
	}

	/**
	 * Add all standard attributes to the product in magento model
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertProductsAttributes(){
		$columns = $this->getWorker()->getConfig()->getColumns();
		foreach ($columns as $name => $params){
			if (isset($params['attr'])){
				$this->InsertProductsAttribute($name, $params['type'], !empty($params['select']));
			}
		}

		return $this;
	}

	/**
	 * Add the attribute $name to the product in magento model
	 * @param string $name name of attribute
	 * @param string $type type of attribute. Is used for the selection of the eva table.
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertProductsAttribute($name, $type, $select = false){
		$resource = Mage::getSingleton('core/resource');
		$columns = $this->getWorker()->getConfig()->getColumns();

		$comment = 'Set ' . $name . '_id variable';
		$sql = 'SELECT attribute_id INTO @' . $name . '_id FROM ' . $resource->getTableName('eav_attribute') . '
			WHERE entity_type_id=@product_entity_type_id
			AND attribute_code = "'.$name.'";';
		$this->getWorker()->addQuery($sql, $comment);

		$comment = 'Add ' . $name . ' attribute for products';

		if($select){
			//DELETE attributes so it would be possible to clear attribute values providing an empty cell in the CSV file
			//Required only for Updating existing products
			if(!array_key_exists('attribute_set', $columns)){ 
				$sql = 'DELETE FROM ' . $resource->getTableName('catalog_product_entity_int') . '
				WHERE entity_type_id = @product_entity_type_id 
					AND attribute_id = @' . $name . '_id
					AND store_id = @store_id
					AND entity_id IN (
						SELECT b.magento_entity_id
						FROM ' . $this->getWorker()->getTableName() . ' b
						WHERE b.magento_entity_id IS NOT NULL
					);';

				$this->getWorker()->addQuery($sql, $comment);
			}

			$this->CreateAttributeValuesProcedure($name);

			$comment = 'Adding new values for ' . $name . ' attribute';
			$sql = 'CALL createNewAttributeValues_' . $this->getLogPrefix() . '();';
			$this->getWorker()->addQuery($sql, $comment);

			$sql = 'REPLACE INTO ' . $resource->getTableName('catalog_product_entity_int') . ' (entity_type_id, attribute_id, store_id, entity_id, value)
				SELECT @product_entity_type_id, @'.$name.'_id, @store_id, b.magento_entity_id, valueId
				FROM ' . $this->getWorker()->getTableName() . ' b
				LEFT JOIN (
				SELECT Distinct eaov.option_id as valueId, eaov.value as valueStr
					FROM ' . $resource->getTableName('eav_attribute_option') . ' eao 
					LEFT JOIN ' . $resource->getTableName('eav_attribute_option_value') . ' eaov ON eaov.option_id = eao.option_id
					WHERE eao.attribute_id = @' . $name . '_id AND store_id = @store_id
				) AS optVal ON optVal.valueStr = TRIM(b.' . $name . ')
				WHERE b.' . $name . ' IS NOT NULL AND b.' . $name . ' <> "" AND b.magento_entity_id IS NOT NULL; '; 
			$this->getWorker()->addQuery($sql, $comment);

			$this->DropAttributeValuesProcedure();
		}
		else {
			//DELETE attributes so it would be possible to clear attribute values providing an empty cell in the CSV file
			//Required only for Updating existing products
			if(!array_key_exists('attribute_set', $columns)){ 
				$sql = 'DELETE FROM ' . $resource->getTableName('catalog_product_entity_' . $type ) . ' 
				WHERE entity_type_id = @product_entity_type_id 
					AND attribute_id = @'.$name.'_id
					AND store_id = @store_id
					AND entity_id IN (
						SELECT b.magento_entity_id
						FROM ' . $this->getWorker()->getTableName() . ' b
						WHERE b.magento_entity_id IS NOT NULL
					);'; 
				$this->getWorker()->addQuery($sql, $comment);
			}

			if($type == 'decimal'){
				if (isset($columns[$name]['multiplier']) && $columns[$name]['multiplier'] == true){
					$comment = "Updating prices";
					$sql = "UPDATE " . $this->getWorker()->getTableName() . " SET "  . $name . "="  . $name . "*@multiplier;";
					$this->getWorker()->addQuery($sql, $comment);
				}
				
				$sql = 'REPLACE INTO ' . $resource->getTableName('catalog_product_entity_' . $type) . ' (entity_type_id, attribute_id, store_id, entity_id, value)
					SELECT @product_entity_type_id, @'.$name.'_id, @store_id, b.magento_entity_id, b.'.$name.'
					FROM ' . $this->getWorker()->getTableName() . ' b
					WHERE b.' . $name . ' IS NOT NULL AND b.' . $name . ' > "0" AND b.magento_entity_id IS NOT NULL;'; 

				$this->getWorker()->addQuery($sql, $comment);

			}elseif($type == 'datetime'){
				$sql = 'REPLACE INTO ' . $resource->getTableName('catalog_product_entity_' . $type) . ' (entity_type_id, attribute_id, store_id, entity_id, value)
					SELECT @product_entity_type_id, @'.$name.'_id, @store_id, b.magento_entity_id, b.'.$name.'
					FROM ' . $this->getWorker()->getTableName() . ' b
					WHERE b.'.$name.' IS NOT NULL AND b.'.$name.' > "2000-01-01" AND b.magento_entity_id IS NOT NULL;';

				$this->getWorker()->addQuery($sql, $comment);
			}else {
				$selectCondition = $name == 'original_url' ? 'b.original_url' : 'CONCAT( UPPER( LEFT( b.'.$name.' , 1 ) ) , LOWER(SUBSTRING( b.'.$name.' , 2 )) )';
				$sql = 'REPLACE INTO ' . $resource->getTableName('catalog_product_entity_' . $type) . ' (entity_type_id, attribute_id, store_id, entity_id, value)
					SELECT @product_entity_type_id, @'.$name.'_id, @store_id, b.magento_entity_id, ' . $selectCondition . '
					FROM ' . $this->getWorker()->getTableName() . ' b
					WHERE b.' . $name . ' IS NOT NULL AND LOWER(b.' . $name . ') <> "" AND b.magento_entity_id IS NOT NULL;';

				$this->getWorker()->addQuery($sql, $comment);
			}
		}

		return $this;
	}

	/**
	 * Insert product to categories
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertProductsInCategories(){
		$columns = $this->getWorker()->getConfig()->getColumns();
		$resource = Mage::getSingleton('core/resource');

		if(array_key_exists('cat_id', $columns)){
			$comment = 'Insert Products in categories';

			$field = 'cat_id';
			$this->CreateExplodeProcedure($field);

			$sql = "CALL explode_table_" . $this->getLogPrefix() . $field . "(',');";
			$this->getWorker()->addQuery($sql);
			
			$sql = "DELETE FROM " . $resource->getTableName('catalog_category_product') . " WHERE product_id IN (
					SELECT DISTINCT tb.id from " . $this->getLogPrefix() . " tb);";

			$this->getWorker()->addQuery($sql);

			$sql = "REPLACE INTO " . $resource->getTableName('catalog_category_product') . " (category_id, product_id, position)
				SELECT tb.value, tb.id, 0
				FROM " . $this->getLogPrefix() . " tb;";

			$this->getWorker()->addQuery($sql, $comment);

			$sql = "DELETE FROM " . $resource->getTableName('catalog_category_product_index') . " WHERE product_id IN (
					SELECT DISTINCT tb.id from " . $this->getLogPrefix() . " tb);
				REPLACE INTO " . $resource->getTableName('catalog_category_product_index') . " (category_id, product_id, position, is_parent, store_id, visibility)
				SELECT tb.value, tb.id, 0, 0, @store_id, b.visibility
				FROM " . $this->getLogPrefix() . " tb
				LEFT JOIN " . $this->getLogPrefix() . " b ON tb.id = b.magento_entity_id;";

			$this->getWorker()->addQuery($sql, $comment);
			$this->DropExplodeProcedure($field);
		}

		return $this;
	}

	/**
	 * Add link in catalog_product_website magento model
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertProductsWebsiteLink(){
		$columns = $this->getWorker()->getConfig()->getColumns();
		$resource = Mage::getSingleton('core/resource');

		if(array_key_exists('websites', $columns)){
			$comment = 'SET catalog_product_website link products';

			$field = 'websites';
			$this->CreateExplodeProcedure($field);
			$sql = "CALL explode_table_" . $this->getLogPrefix() . $field . "(',');
				DELETE FROM " . $resource->getTableName('catalog_product_website') . " WHERE product_id IN (
					SELECT DISTINCT tb.id FROM " . $this->getLogPrefix() . " tb);
				REPLACE INTO " . $resource->getTableName('catalog_product_website') . " (product_id, website_id)
				SELECT tb.id, cw.website_id
				FROM " . $this->getLogPrefix() . " tb
				LEFT JOIN " . $resource->getTableName('core_website') . " cw ON tb.value = cw.code;";

			$this->getWorker()->addQuery($sql, $comment);

			$this->DropExplodeProcedure($field);
		}

		return $this;
	}

	/**
	 * Inserting relations for configurables and simples products
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertProductsRelations(){
		$columns = $this->getWorker()->getConfig()->getColumns();
		$resource = Mage::getSingleton('core/resource');

		if(array_key_exists('association_id', $columns)){
			$comment = 'REPLACE relations between configurable and simple products';
			$sql = "REPLACE INTO " . $resource->getTableName('catalog_product_relation') . " (parent_id, child_id)
				SELECT cpe.entity_id, b.magento_entity_id
				FROM " . $this->getWorker()->getTableName() . " b
				LEFT JOIN " . $resource->getTableName('catalog_product_entity') . " cpe ON b.association_id = cpe.sku
				WHERE b.magento_entity_id IS NOT NULL AND cpe.entity_id IS NOT NULL AND b.type='simple';";		
			$this->getWorker()->addQuery($sql, $comment);
			
			$sql = "REPLACE INTO " . $resource->getTableName('catalog_product_super_link') . " (parent_id, product_id)
				SELECT cpe.entity_id, b.magento_entity_id
				FROM " . $this->getWorker()->getTableName() . " b
				LEFT JOIN " . $resource->getTableName('catalog_product_entity') . " cpe ON b.association_id = cpe.sku
				WHERE b.magento_entity_id IS NOT NULL AND cpe.entity_id IS NOT NULL AND b.type='simple';";
			$this->getWorker()->addQuery($sql, $comment);

			$comment = 'Set @options_container_id variable';
			$sql = 'SELECT attribute_id INTO @options_container_id FROM ' . $resource->getTableName('eav_attribute') . '
			WHERE entity_type_id=@product_entity_type_id
			AND attribute_code = "options_container";';
			$this->getWorker()->addQuery($sql, $comment);

			$sql = 'REPLACE INTO ' . $resource->getTableName('catalog_product_entity_varchar') . ' (entity_type_id, attribute_id, store_id, entity_id, value)
			SELECT @product_entity_type_id, @options_container_id, @store_id, b.magento_entity_id, "container1"
			FROM ' . $this->getWorker()->getTableName() . ' b
			WHERE b.magento_entity_id IS NOT NULL;';
			$this->getWorker()->addQuery($sql, $comment);

			if(array_key_exists('configurable_attributes', $columns)){
				$comment = 'Insert configurable attributes for configurable products';

				$field = 'configurable_attributes';
				$this->CreateExplodeProcedure($field);

				$sql = "CALL explode_table_" . $this->getLogPrefix() . $field . "(',');
					DELETE FROM " . $resource->getTableName('catalog_product_super_attribute') . " WHERE product_id IN (
						SELECT DISTINCT tb.id FROM " . $this->getLogPrefix() . " tb);
					REPLACE INTO " . $resource->getTableName('catalog_product_super_attribute') . " (product_id, attribute_id)
					SELECT tb.id, eav.attribute_id
					FROM " . $this->getLogPrefix() . " tb
					LEFT JOIN " . $resource->getTableName('eav_attribute') . " eav ON tb.value = eav.attribute_code
					WHERE eav.entity_type_id = @product_entity_type_id
					AND tb.id IS NOT NULL;";

				$this->getWorker()->addQuery($sql, $comment);

				$this->DropExplodeProcedure($field);
			}
		}

		return $this;
	}

	/**
	 * Add core url Rewriting
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertCoreUrlRewrite()
	{
		$comment = 'Prepare id_path field';
		$sql = '
		ALTER TABLE ' . $this->getWorker()->getTableName() . ' ADD COLUMN id_path VARCHAR(255) DEFAULT NULL, ADD COLUMN request_path VARCHAR(255) DEFAULT NULL, 
		ADD COLUMN target_path VARCHAR(255) DEFAULT NULL;

		UPDATE ' . $this->getWorker()->getTableName() . ' b
			SET b.id_path = CONCAT("product/", b.magento_entity_id) 
			WHERE b.magento_entity_id IS NOT NULL;';
		$this->getWorker()->addQuery($sql, $comment);

		$comment = 'Prepare request_path field';
		$sql = 'UPDATE ' . $this->getWorker()->getTableName() . ' b
			SET b.request_path = CONCAT(b.url_key, ".html") 
			WHERE b.magento_entity_id IS NOT NULL;';
		$this->getWorker()->addQuery($sql, $comment);

		$comment = 'Prepare target_path field';
		$sql = 'UPDATE ' . $this->getWorker()->getTableName() . ' b
			SET b.target_path = CONCAT("catalog/product/view/id/", b.magento_entity_id) 
			WHERE b.magento_entity_id IS NOT NULL;';
		$this->getWorker()->addQuery($sql, $comment);

		$comment = 'ADD an entry in URL Rewrite table';
		$sql = 'REPLACE INTO core_url_rewrite (store_id, category_id, product_id, id_path, request_path, target_path, is_system, options, description)
			SELECT @front_store_id, NULL, b.magento_entity_id, b.id_path, b.request_path, b.target_path, 1, "", NULL
			FROM ' . $this->getWorker()->getTableName() . ' b
			WHERE b.magento_entity_id IS NOT NULL;';
		$this->getWorker()->addQuery($sql, $comment);

		return $this;
	}


	/**
	 * Create stored procedure to process comma delimited values
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function CreateExplodeProcedure($fieldName){
		$comment = 'Create stored procedure to process comma delimited values';

		$sql = "
		CREATE PROCEDURE explode_table_" . $this->getLogPrefix() . $fieldName . "(bound VARCHAR(255))
			BEGIN
				DECLARE id INT DEFAULT 0;
				DECLARE value TEXT;
				DECLARE occurance INT DEFAULT 0;
				DECLARE i INT DEFAULT 0;
				DECLARE splitted_value TEXT;
				DECLARE done INT DEFAULT 0;
				DECLARE cur1 CURSOR FOR SELECT magento_entity_id, " . $fieldName . " FROM
				" . $this->getWorker()->getTableName() . " WHERE " . $fieldName . " != '' AND magento_entity_id IS NOT NULL;
				DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
				DROP TEMPORARY TABLE IF EXISTS " . $this->getLogPrefix() . ";
				CREATE TEMPORARY TABLE " . $this->getLogPrefix() . "(
				`id` INT NOT NULL,
				`value` VARCHAR(255) NOT NULL
				) ENGINE=Memory;
				OPEN cur1;
					read_loop: LOOP
					FETCH cur1 INTO id, value;
					IF done THEN
					  LEAVE read_loop;
					END IF;
					SET occurance = (SELECT LENGTH(value)
											 - LENGTH(REPLACE(value, bound, ''))
											 +1);
					SET i=1;
					WHILE i <= occurance DO
						SET splitted_value =
						(SELECT REPLACE(SUBSTRING(SUBSTRING_INDEX(value, bound, i),
						LENGTH(SUBSTRING_INDEX(value, bound, i - 1)) + 1), ',', ''));
						INSERT INTO " . $this->getLogPrefix() . " VALUES (id, splitted_value);
						SET i = i + 1;
					END WHILE;
				END LOOP;
				CLOSE cur1;
			END;";
		$this->getWorker()->addQuery($sql, $comment);

		return $this;
	}

	 /**
	 * Drop stored procedure to process comma delimited values
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function DropExplodeProcedure($fieldName){
		$comment = 'Drop stored procedure to process comma delimited values';
		$sql = "DROP PROCEDURE IF EXISTS explode_table_" . $this->getLogPrefix() . $fieldName . ";";

		$this->getWorker()->addQuery($sql, $comment);

		return $this;
	}


	/**
	 * Create stored procedure to add new select attribute values to the database
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function CreateAttributeValuesProcedure($attributeName){
		$resource = Mage::getSingleton('core/resource');
		$comment = 'Create stored procedure to add new select attribute values to the database';

		$sql = "DROP PROCEDURE IF EXISTS createNewAttributeValues_" . $this->getLogPrefix() . ";
		CREATE PROCEDURE createNewAttributeValues_" . $this->getLogPrefix() . "()
		BEGIN
		  DECLARE done INT DEFAULT 0;
		  DECLARE attrVal CHAR(32);
		  DECLARE cur1 CURSOR FOR SELECT DISTINCT(b." . $attributeName . ") FROM " . $this->getWorker()->getTableName() . " b WHERE b." . $attributeName . " <>'' AND b.magento_entity_id IS NOT NULL AND b." . $attributeName . " NOT IN 
			(SELECT Distinct eaov.value as valueStr
				FROM " . $resource->getTableName('eav_attribute_option') . " eao 
				LEFT JOIN " . $resource->getTableName('eav_attribute_option_value') . " eaov ON eaov.option_id = eao.option_id
				WHERE eao.attribute_id = @" . $attributeName . "_id AND eaov.value IS NOT NULL);
		  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

		  OPEN cur1;

		  read_loop: LOOP
			FETCH cur1 INTO attrVal;
			IF done THEN
			  LEAVE read_loop;
			END IF;

			INSERT INTO " . $resource->getTableName('eav_attribute_option') . " VALUES(NULL, @" . $attributeName . "_id, 0);
			INSERT INTO " . $resource->getTableName('eav_attribute_option_value') . " VALUES (NULL, LAST_INSERT_ID(), 0, attrVal);

		  END LOOP;

		  CLOSE cur1;
		END;";

		$this->getWorker()->addQuery($sql, $comment);

		return $this;
	}

	/**
	 * Drop stored procedure to add new select attribute values to the database
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function DropAttributeValuesProcedure(){
		$comment = 'Drop stored procedure to add new select attribute values to the database';

		$sql = 'DROP PROCEDURE IF EXISTS createNewAttributeValues_' . $this->getLogPrefix() . ';';

		$this->getWorker()->addQuery($sql, $comment);

		return $this;
	}

	/**
	 * Insert inventory configuration for all products
	 * @return TC_Import_Model_Product_Bridge
	 */
	protected function InsertProductsInventory(){
		$columns = $this->getWorker()->getConfig()->getColumns();
		$resource = Mage::getSingleton('core/resource');

		if(array_key_exists('is_in_stock', $columns)){
			$comment = 'Add inventory configuration for products';

			$necessaryColumns = array(
					'min_qty' => "0.0000",
					'use_config_min_qty'=> 1,
					'is_qty_decimal'=> 0,
					'backorders'=> 0,
					'use_config_backorders'=> 1,
					'min_sale_qty'=> "1.0000",
					'use_config_min_sale_qty'=> 1,
					'max_sale_qty' => "0.0000",
					'use_config_max_sale_qty'=> 1,
					'is_in_stock'=> 0,
					'qty' => 0,
					'low_stock_date'=> 'NULL',
					'notify_stock_qty'=> 'NULL',
					'use_config_notify_stock_qty'=> 1,
					'manage_stock'=> 0, 
					'use_config_manage_stock'=> 0, 
					'use_config_qty_increments'=> 1,
					'qty_increments'=> "0.0000",
					'use_config_enable_qty_increments'=> 1,
					'enable_qty_increments'=> 0
				);

			foreach($necessaryColumns as $name=>$value){
				if(array_key_exists($name, $columns)){
					if($name == 'low_stock_date'){
						$necessaryColumns[$name] = 'IF (b.' . $name.' > "2000-01-01", b.' . $name . ', NULL)';
					}
					elseif ($name == 'notify_stock_qty'){
						$necessaryColumns[$name] = 'IF (b.' . $name . ' > "-1", b.' . $name . ', NULL)';
					}
					else {
						$necessaryColumns[$name] = 'b.' . $name;
					}
				}
			}

			$sql = 'REPLACE INTO ' . $resource->getTableName('cataloginventory_stock_item') . ' (product_id, stock_id, qty, min_qty, use_config_min_qty, is_qty_decimal, backorders, use_config_backorders, min_sale_qty, use_config_min_sale_qty, max_sale_qty, use_config_max_sale_qty, is_in_stock, low_stock_date, notify_stock_qty, use_config_notify_stock_qty, manage_stock, use_config_manage_stock, use_config_qty_increments, qty_increments, use_config_enable_qty_inc, enable_qty_increments)  
				SELECT b.magento_entity_id, @defaultstock_id, '.implode(', ', $necessaryColumns).'
				FROM ' . $this->getWorker()->getTableName() . ' b
				WHERE magento_entity_id IS NOT NULL;';

			$this->getWorker()->addQuery($sql, $comment);

//			$comment = 'Set stock status for products';
//			$sql = 'REPLACE INTO ' . $resource->getTableName('cataloginventory_stock_status') . ' (product_id, website_id, stock_id, qty, stock_status)
//				SELECT b.magento_entity_id, @website_id, @defaultstock_id, b.qty, b.is_in_stock
//				FROM ' . $this->getWorker()->getTableName() . ' b
//				WHERE magento_entity_id IS NOT NULL;';
//			$this->getWorker()->addQuery($sql, $comment);
		}

		return $this;
	}

}
