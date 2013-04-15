<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Product_Populating_Config extends Zend_Config_Json{

	const CONF_FILE = 'table.json';
	protected $_columns = null;

	public function __construct(){
		parent::__construct(Mage::getModuleDir('data', 'TC_Import') . DS . self::CONF_FILE);
	}

	/**
	 * Returns columns for temporary import table
	 * @return array()
	 */
	public function getColumns(){
		if ($this->_columns === null){
			$data = $this->toArray();

			if (!isset($data['columns'])){
				throw new Exception('Section not found');
			}

			$this->_columns = $data['columns'];
		}

		return $this->_columns;
	}

	/**
	 * Get strict correspondence from catalog_product_entity_* tables
	 * @param string $type
	 * @param string $info field name added in exception message
	 * @return string
	 * @throws Exception
	 */
	public static function getTypeForDatabase($type, $column = '') {
		switch ($type) {
			case TC_Import_Model_Product_Bridge::SKIP:
				return "TEXT";
				break;
			case "int":
			case "integer":
				return "INT(11)";
				break;
			case "varchar":
				return "VARCHAR(255)";
				break;
			case "text":
				return "TEXT";
				break;
			case "datetime":
			case "date":
				return "DATETIME";
				break;
			case "float":
			case "decimal":
				return "DECIMAL(12,4)";
				break;
			case "boolean":
				return "INT(1)";
				break;
			default:
				throw new Exception('No correspondence type for: ' . $column);
				break;
		}
	}
}