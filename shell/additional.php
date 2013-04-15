<?php
require_once 'abstract.php';

class TC_Shell_Additional extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        /* @var $factory TC_Import_Model_Additional_Factory */
        $factory = Mage::getModel('tcimport/additional_factory');
        if (isset($this->_args['cleanProducts'])) {
            /** @var $process TC_Import_Model_Additional_Process */
            $process = $factory::init('additional');
            if ($this->getArg('from') !== false){
                $process->setFrom($this->getArg('from'));
            }

            $process->run();
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f additional.php -- [options]

  cleanProducts                                 Start removing products from magento DB using data in external database
  cleanProducts --from  [date e.g. 31.01.2013]  if from is not set then we'll use today subtract 1 day
  help                                              This help

USAGE;
    }
}

$shell = new TC_Shell_Additional();
$shell->run();
