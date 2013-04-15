<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

require_once 'abstract.php';

class TC_Shell_ProductImport extends Mage_Shell_Abstract{

	/**
	 * Run script
	 *
	 */
	public function run()
	{
		$factory = Mage::getModel('tcimport/import');
		if (isset($this->_args['startAccumulating'])) {
			$process = $factory::init('product');
			$process = $this->_checkLimit($process);
			$process->run();
		}elseif (isset($this->_args['startPopulating'])) {
			Mage::getModel('tcimport/product_bridge')->run();
		}elseif (isset($this->_args['startImagesProcess'])) {
			$process = $factory::init('images');
			$process = $this->_checkLimit($process);
			$process->run();
		} else {
			echo $this->usageHelp();
		}
	}

	/**
	 * Check limit arguments and applied them
	 * @param  TC_Import_Model_Processor $process
	 * @return TC_Import_Model_Processor
	 */
	protected function _checkLimit($process){
		if ($this->getArg('limit') !== false){
			$limit = explode(',', $this->getArg('limit'));
			if (!isset($limit[1])){
				$limit[1] = null;
			}

			$process->setLimit($limit[0], $limit[1]);
		}

		return $process;
	}
	/**
	 * Retrieve Usage Help Message
	 *
	 */
	public function usageHelp()
	{
		return <<<USAGE
Usage:  php -f product_import.php -- [options]

  startAccumulating                                 Start data accumulating
  startAccumulating --limit  [count],[offset]       Adds limit to configurables select sql query for test mode
  startPopulating                                   Start data populating
  startImagesProcess                                Start images processing
  startImagesProcess --limit  [count],[offset]      Start images processing with limit
  help                                              This help

USAGE;
	}
}

$shell = new TC_Shell_ProductImport();
$shell->run();
