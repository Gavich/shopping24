<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Product_Processor extends TC_Import_Model_Processor{

	public static $_dirs = array(
		'NEW' => 'new',
		'PROCESSED' => 'processed',
		'WORKING' => 'working',
		'ERROR' => 'error',
		'IMAGES' => 'images'
	);
	public static $_workingDir = 'tcimport';
	protected $_offset = null;
	protected $_count = null;

	const CONF_TABLE = 'art_init';
	const SIMPLE_TABLE = 'params';
	const TEXT_ATTRIBUTES_TABLE = 'texts';

	/**
	 * Get prefix for loger storage name
	 * @return  string
	 */
	public function getLogPrefix(){
		return 'products';
	}

	/**
	 * Run process
	 * @return  bool
	 */
	public function run(){
		set_time_limit(0);
		$this->_beforeRun();

		$this->log('Starting generation csv files..');
		$csv = Mage::getModel('tcimport/product_csv')
				->setConfig($this->getConfig())
				->setPath($this->getPath('NEW'));

		//getting configurable products
		$confProducts = $this->_getConfigurables();

		//fields for simple products seect object
		$simpleFields = $this->getConfig()->getSelectFields('simple');
		//group by multiple columns to exclude duplicated products
		$group = new Zend_Db_Expr("CONCAT (" . implode(',', $this->getConfig()->getSelectFields('unique_filter')) . ") ");
		$simpleFields['allAttributes'] = $group;

		while ($product = $confProducts->fetch()) {
			$this->log('Preparing csv for: ' . $product['original_id']);

			$products = array();
			$product['type'] = 'configurable';

			$products[] = $product;

			try{
				//getting child products
				Varien_Profiler::start('fetching_simple');
				$simpleSelect = $this->getAdapter()->select()
					->from(self::SIMPLE_TABLE, $simpleFields)
					->where('art_init_id=?', $product['id_field'])
					->having('allAttributes <> ""')
					->group($group);

				$simpleProducts = $this->getAdapter()->fetchAll($simpleSelect);
				Varien_Profiler::stop('fetching_simple');

				$this->log('Simple products found for ' . $product['original_id'] . ': ' . count($simpleProducts) . '. Time spent: ' . Varien_Profiler::fetch('fetching_simple'));
				Varien_Profiler::reset('fetching_simple');

				if (!empty($simpleProducts)){
					foreach ($simpleProducts as &$item){
						$item['type'] = 'simple';
						unset($item['allAttributes']);
					}

					$products = array_merge($products, $simpleProducts);
				}else{
					//if product has not childs this is error and it should not be created
                                        $this->log('Product has not childs this is error and it should not be created: ' . $product['original_id']);
					continue;
				}

				//Writing products to CSV file for import
				Varien_Profiler::start('writing_csv');
				$csv
					->setProducts($products)
					->write($product['original_id'])
					->clear();
				Varien_Profiler::stop('writing_csv');

				$this->log('Done. Time spent for writing: ' . Varien_Profiler::fetch('writing_csv'));
				Varien_Profiler::reset('writing_csv');
			}catch(Exception $e){
				$this->log($e->getMessage(), 3);
			}
		}

		return true;
	}

	/**
	 * Executed before main process run
	 * @return TC_Import_Model_Product_Processor
	 */
	private function _beforeRun(){
		$this->log('Before run call');

		$varDir = Mage::getBaseDir('var');

		if (!is_writable($varDir)){
			$this->_terminateCriticalError('VAR dir is not writeable');
		}

		//creating required directoires
		$workingDir = $varDir . DS . self::$_workingDir;
		foreach (self::$_dirs as $dir) {
			$fullPath = $workingDir . DS . $dir;
			if(!is_dir($fullPath)){
				$this->log(sprintf('Creating dir: "%s"', $dir));
				mkdir($fullPath, 0777, true);
			}
		}

		Varien_Profiler::enable();

		$this->log('Before run finished');
		return $this;
	}

	/**
	 * Get init_products from external table
	 * @return Array()
	 */
	protected function _getConfigurables(){
		$fields = $this->getConfig()->getSelectFields('configurable', 'ct');

		//new method to retrieve categories
		$fields['cat_id'] = new Zend_Db_Expr("(SELECT GROUP_CONCAT(p.category_id) FROM product_cat AS p WHERE p.id = ct.original_id GROUP BY p.id)");
		$fields['sku'] = 'original_id';
		$fieldsTexts = $this->getConfig()->getSelectFields('texts', 'a');

		try{
			Varien_Profiler::start('fetching_configurable');

			$confSelect = $this->getAdapter()->select()
				->from(array('ct' => self::CONF_TABLE), $fields)
				->join(array('a' => self::TEXT_ATTRIBUTES_TABLE), 'ct.id = a.art_init_id', $fieldsTexts);

			$confSelect->limit($this->_count, $this->_offset);

			$confProducts = $this->getAdapter()->query($confSelect);
			Varien_Profiler::stop('fetching_configurable');

			$time = Varien_Profiler::fetch('fetching_configurable');
			$this->log('SQL RESPONSE received. Time spent: ' . $time);
		}catch(Exception $e){
			$this->_terminateCriticalError($e->getMessage());
		}

		return $confProducts;
	}

	/**
	 * Returns path for process specific dirs
	 * @param  string $dir Dir code
	 * @return string | bool false
	 */
	public static function getPath($dir){
		$varDir = Mage::getBaseDir('var');

		if(!isset(self::$_dirs[$dir]))
			return false;

		$workingDir = $varDir . DS . self::$_workingDir;

		return $workingDir . DS . self::$_dirs[$dir];
	}

	/**
	 * Setter for limit property
	 * @return TC_Import_Model_Product_Config
	 */
	public function setLimit($count = null, $offset = null){
		$this->_count = $count;
		$this->_offset = $offset;

		return $this;
	}
}