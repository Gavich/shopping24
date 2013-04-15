<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

abstract class TC_Import_Model_Processor{

	/** @var TC_Import_Model_Loger_Abstract **/
	protected $_logInstance;
	protected $_connection;
	protected $_config = null;

	/**
	 * Get prefix for loger storage name
	 * @return  string
	 */
	public abstract function getLogPrefix();

	/**
	 * Run process
	 * @return  bool
	 */
	public abstract function run();

	/**
	 * Setter for loger object
	 * @param TC_Import_Model_Loger_Abstract $loger
	 * @return TC_Import_Model_Processor
	 */
	public function setLoger(TC_Import_Model_Loger_Abstract $loger){
		$this->_logInstance = $loger;

		return $this;
	}

	/**
	 * Returns loger object
	 * @return TC_Import_Model_Loger_Abstract
	 */
	protected function getLoger(){
		return $this->_logInstance;
	}

	/**
	 * Log message to loger's storage
	 * @param  string $message
	 * @param  integer $level
	 * @return void(0)
	 */
	protected function log($message, $level = 6){
		if (Mage::getStoreConfig('tcimport_database/tcimport_import_group/logs') == '0')
			return false;

		$this->getLoger()->log($message, $level);
	}

	/**
	 *  Sets connection object
	 * @param $connection 
	 * @return TC_Import_Model_Processor
	 */
	public function setAdapter($connection){
		$this->_connection = $connection;

		return $this;
	}

	/**
	 * Getter for config object
	 * @return TC_Import_Model_Product_Config
	 */
	public function getConfig(){
		if($this->_config == null){
			$this->_config = Mage::getModel('tcimport/product_config');
		}

		return $this->_config;
	}

	/**
	 * Return connection object
	 * @return Varien_Db_Adapter_Interface
	 */
	protected function getAdapter(){
		return $this->_connection;
	}

	/**
	 * Terminating process due to critical error
	 * @param  string $msg
	 * @return void(0) - terminating process
	 */
	protected function _terminateCriticalError($msg = 'Error occured. Terminating...'){
		if ($this->_logInstance instanceof TC_Import_Model_Loger_Abstract){
			$this->log($msg, 1);
		}
		die($msg);
	}
}
