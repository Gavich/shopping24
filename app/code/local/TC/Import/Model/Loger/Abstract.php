<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

abstract class TC_Import_Model_Loger_Abstract{

	protected $_process;

	const LOG_DIR = 'TCImport';

	public function __construct(){
		$logDirPath = Mage::getBaseDir('log') . DS . self::LOG_DIR;
		if(!file_exists($logDirPath) || !is_dir($logDirPath)){
			mkdir ($logDirPath, 0777);
		}
	}

	/**
	 * Add message to log instance
	 * @param  string $message
	 * @param  int $level Message type
	 * @return void(0)
	 */
	public abstract function log($message, $level);

	/**
	 * Set processor object
	 * @param TC_Import_Model_Processor $processor
	 * @return TC_Import_Model_Loger_Abstract
	 */
	public function setProcess(TC_Import_Model_Processor $processor){
		$this->_process = $processor;

		return $this;
	}

	/**
	 * Get processor object
	 * @return TC_Import_Model_Processor $processor
	 */
	public function getProcess(){
		return $this->_process;
	}
}