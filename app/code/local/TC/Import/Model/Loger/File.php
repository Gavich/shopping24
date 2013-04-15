<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Loger_File extends TC_Import_Model_Loger_Abstract{

	/**
	 * Add message to log file
	 * @param  string $message
	 * @param  int $level Message type
	 * @return void(0)
	 */
	public function log($message, $level){
		Mage::log($message, $level, parent::LOG_DIR . DS . $this->getProcess()->getLogPrefix() . '.log');
	}
}