<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

require_once 'abstract.php';

class TC_Shell_Updater extends Mage_Shell_Abstract{

	/** @var array(magento_entity_id => key_value) */
	protected $_key = array();
	protected $_attribute = null;

	const TABLE_NAME = 'updater_tmp';

	/**
	 * Run script
	 *
	 */
	public function run()
	{
		if (isset($this->_args['run']) && isset($this->_args['c'])) {
			$config = new Zend_Config_Json($this->_getRootPath() . 'shell' . DS . 'updater' . DS . $this->_args['c']);

			$config = $config->toArray();

			if (!isset($config['source'])){
				die('Source database doesn\'t specified');
			}

			$resource = Mage::getModel('core/resource');
			/** @var $connection Varien_Db_Adapter_Pdo_Mysql */
			$connection = $resource->getConnection('core_write');

			try{
				$connectionExt = Mage::getModel('core/resource')->createConnection('updater', $config['source']['type'], $config['source']);
			}catch (Exception $e){
				die($e->getMessage());
			}

			$this->_prepareKey($config);

			//get all data from external DB
			$sql = $connectionExt->select()
				->from(array('upd' => $config['update']['table_name']), array('key' => $config['update']['key_field_name'], 'value' => $config['update']['field_name']))
				->where($config['update']['field_name'] . " IS NOT NULL AND " . $config['update']['field_name'] . " != ''");
			$data = $connectionExt->fetchPairs($sql);

			if (empty($data)){
				die('Nothing to update');
			}

			$connection->query("
				CREATE TABLE IF NOT EXISTS " . self::TABLE_NAME . " (
  					`entity_id` INT NOT NULL,
  					`entity_type_id` INT NOT NULL,
  					`store_id` INT NOT NULL,
  					`attribute_id` INT NOT NULL,
  					`value` " . TC_Import_Model_Product_Populating_Config::getTypeForDatabase($config['update']['field_type']) . " NOT NULL,
  					PRIMARY KEY (`entity_id`)
				) ENGINE=InnoDb  DEFAULT CHARSET=utf8;
				TRUNCATE " . self::TABLE_NAME . ";
			");

			$connection->beginTransaction();
			$count = 0;
			$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode($config['update']['entity_type'], $config['update']['attribute_name']);
			$attributeToUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);

			foreach($data as $key => $value){
				try{
					$key = strtolower($key);
					$magentoId = array_search($key, $this->_key);

					if ($magentoId == false){
						continue;
					}

					$connection->insert(self::TABLE_NAME, array(
						'entity_id' => $magentoId,
						'entity_type_id' => $attributeToUpdate->getEntityTypeId(),
						'store_id' => 0,
						'attribute_id' => $attributeToUpdate->getId(),
						'value' => $value
					));

					$count++;

					if ($count > 1000){
						$connection->commit();
						$count = 0;
						$connection->beginTransaction();
					}
				}catch (Exception $e){
					$connection->rollback();
				}
			}

			if ($count > 0){
				$connection->commit();
			}

			if ($config['update']['update_children'] == true){
				$this->_updateChildren($config);
			}

			$connection->query("
				INSERT INTO " . $attributeToUpdate->getBackendTable() . " (entity_type_id, attribute_id, store_id, entity_id, value)
						SELECT b.entity_type_id, b.attribute_id, b.store_id, b.entity_id, b.value
						FROM " . self::TABLE_NAME . " as b ON DUPLICATE KEY UPDATE value=b.value;
			");

			$connection->query("DROP TABLE " . self::TABLE_NAME);
			echo 'Done';
		} else {
			echo $this->usageHelp();
		}
	}

	/**
	 * Update simple products
	 * @param $connection
	 * @param $config
	 * @return bool
	 */
	protected function _updateChildren($config){
		if ($config['update']['entity_type'] != 'catalog_product'){
			return false;
		}

		$resource = Mage::getSingleton('core/resource');
		$connection =  $resource->getConnection('core_write');

		$sql = "
			DROP PROCEDURE IF EXISTS update_simple_products;
			CREATE PROCEDURE update_simple_products()
			BEGIN
				DECLARE done INT DEFAULT 0;
				DECLARE magentoId INT;
				DECLARE cur1 CURSOR FOR SELECT entity_id FROM " . self::TABLE_NAME . ";
				DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

				OPEN cur1;

				read_loop: LOOP
					FETCH cur1 INTO magentoId;
					IF done THEN
						LEAVE read_loop;
					END IF;

					INSERT IGNORE INTO " . self::TABLE_NAME . " (entity_id, entity_type_id, store_id, attribute_id, value) (
						SELECT cpr.child_id, tmp.entity_type_id, tmp.store_id, tmp.attribute_id, tmp.value as entity_id FROM " . $resource->getTableName('catalog_product_relation') . " as cpr
						LEFT JOIN " . self::TABLE_NAME . " as tmp ON tmp.entity_id = cpr.parent_id WHERE cpr.parent_id = magentoId);
				END LOOP;

			  CLOSE cur1;
			END;
		";

		try{
			$connection->query($sql);
			$connection->query("CALL update_simple_products();");
			$connection->query("DROP PROCEDURE IF EXISTS update_simple_products;");
		}catch (Exception $e){
			echo $e->getMessage();
			$connection->query("DROP PROCEDURE IF EXISTS update_simple_products;");
			return false;
		}
	}

	/**
	 * Prepare key field array
	 * @param Zend_Config_Json $config
	 * @return void(0)
	 */
	protected function _prepareKey($config){
		$resource = Mage::getModel('core/resource');
		$connection = $resource->getConnection('core_read');

		try{
			$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode($config['update']['entity_type'],$config['update']['key_attribute_name']);
			$attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);

			$sql = $connection->select()->from($attribute->getBackendTable(), array('id' => 'entity_id', new Zend_Db_Expr("LOWER(value)")))
				->where('entity_type_id =? ', $attribute->getEntityTypeId())
				->where('attribute_id =?', $attributeId);

			$result = $connection->fetchPairs($sql);

			if (empty($result)){
				throw new Exception('Products not found for specific attribute key');
			}

		}catch (Exception $e){
			die($e->getMessage());
		}

		$this->_attribute = $attribute;
		$this->_key = $result;
	}

	/**
	 * Retrieve Usage Help Message
	 *
	 */
	public function usageHelp()
	{
		return <<<USAGE
Usage:  php -f udapter.php -- [options]
Config file placed at folder MAGE_ROOT/shell/update/

  run  --c [config_file_name]                       Run shell command with config from folder updater
  help                                              This help

USAGE;
	}
}

$shell = new TC_Shell_Updater();
$shell->run();
