<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Product_Fs{

	/**
	 * Copy file from working directory to processed
	 * @param  $file string
	 * @return void(0)
	 */
	public static function markProcessed($file){
		$from = TC_Import_Model_Product_Processor::getPath('WORKING') . DS . $file;
		$to = TC_Import_Model_Product_Processor::getPath('PROCESSED') . DS . $file;

		exec('mv ' . $from . ' ' . $to);
	}

	/**
	 * Copy file from new directory to working directory
	 * @param  $file string
	 * @return void(0)
	 */
	public static function markWorking($file){
		$from = TC_Import_Model_Product_Processor::getPath('NEW') . DS . $file;
		$to = TC_Import_Model_Product_Processor::getPath('WORKING') . DS . $file;

		if(!rename($from, $to))
			die('File doesnt exist.');
	}

	/**
	 * Copy file from working directory to error directory
	 * @param  $file string
	 * @return void(0)
	 */
	public static function markError($file){
		$from = TC_Import_Model_Product_Processor::getPath('WORKING') . DS . $file;
		$to = TC_Import_Model_Product_Processor::getPath('ERROR') . DS . $file;

		exec('mv ' . $from . ' ' . $to);
	}
}