<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

require_once 'abstract.php';

class TC_Shell_Updater extends Mage_Shell_Abstract{

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

			//get all data from external DB
			$sql = $connectionExt->select()
				->from(array('upd' => $config['update']['table_name']), array('key' => $config['update']['key_field_name'], 'value' => $config['update']['field_name']))
				->where($config['update']['field_name'] . " IS NOT NULL AND " . $config['update']['field_name'] . " != ''");
			$data = $connectionExt->fetchAll($sql);

			if (empty($data)){
				die('Nothing to update');
			}

			$connection->beginTransaction();
			$count = 0;
			$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode($config['update']['entity_type'], $config['update']['attribute_name']);
			/** @var $attributeToUpdate Mage_Catalog_Model_Resource_Eav_Attribute **/
			$attributeToUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);

			if ($attributeToUpdate->getFrontendInput() != 'select'){
				die('Bad attribute, only "selects" allowed to update');
			}

			foreach($data as $arrayToUpdate){
				try{
					$where = array(
						0 => $connection->quoteInto('value = ?', $arrayToUpdate['key']),
						1 => $connection->quoteInto('store_id = ?', 0),
						2 => $connection->quoteInto('option_id IN (?)',
								$connection->select()->from($resource->getTableName('eav_attribute_option'), array('option_id'))
									->where('attribute_id = ?', $attributeId))
					);
					$connection->update($resource->getTableName('eav_attribute_option_value'), array(
						'value' => $arrayToUpdate['value']
					), $where);

					$count++;

					if ($count > 300){
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

			echo PHP_EOL . 'Done' . PHP_EOL;
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
Usage:  php -f udapter.php -- [options]
Config file placed at folder MAGE_ROOT/shell/update/

  run  --c [config_file_name]                       Run shell command with config from folder updater
  help                                              This help

USAGE;
	}
}

$shell = new TC_Shell_Updater();
$shell->run();
