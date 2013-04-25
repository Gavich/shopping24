<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Import
{
    //prefix for core_config_data
    const XML_PATH_PREFIX = 'tcimport_database/tcimport_database_group/';

    /**
     * Factory method for processes
     * @param  string $type
     * @param  string $logMethod
     * @return TC_Import_Model_Processor
     */
    public static function init($type, $logMethod = 'file')
    {
        $processor = Mage::getModel('tcimport/' . $type . '_processor');

        $logInstance = Mage::getModel('tcimport/loger_' . $logMethod)
                            ->setProcess($processor);

        $config = static::getConnectionConfig();
        $connection = Mage::getModel('core/resource')->createConnection('import', $config['type'], $config);

        $processor
            ->setLoger($logInstance)
            ->setAdapter($connection);

        return $processor;
    }

    /**
     * Returns DB config array
     * @return array
     */
    public static function getConnectionConfig()
    {
        $config = array(
            'host' => Mage::getStoreConfig(static::XML_PATH_PREFIX . 'host'),
            'username' => Mage::getStoreConfig(static::XML_PATH_PREFIX . 'user'),
            'password' => Mage::getStoreConfig(static::XML_PATH_PREFIX . 'password'),
            'dbname' => Mage::getStoreConfig(static::XML_PATH_PREFIX . 'name'),
            'initStatements' => 'SET NAMES utf8',
            'model' => 'mysql4',
            'type' => 'pdo_mysql',
            'pdoType' => '' ,
            'active' => '1'
        );

        return $config;
    }
}
