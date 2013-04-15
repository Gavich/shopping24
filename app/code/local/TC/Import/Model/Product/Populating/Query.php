<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Product_Populating_Query{

	protected $_queries = array();
	protected $_connection = null;
	protected $_config = null;
	protected $_loger = null;
	protected $_table = null;

	/**
	 * Object cuonstructor
	 */
	public function __construct(){
		$this->setConfig(Mage::getModel('tcimport/product_populating_config'));
	}

	/**
	 * Creating table by code
	 * @param  $code string
	 * @return TC_Import_Model_Product_Populating_Query
	 */
	public function createTable($code){
		$tableName = 'tcimport_populating_' . $code;
		$this->_table = $tableName;

		// $comment = 'Drop ' . $this->_table . ' table';
		// $sql = 'DROP TABLE IF EXISTS ' . $this->_table . ';';
		// $this->addQuery($sql, $comment);

		$comment = 'Create ' . $this->_table . ' table';

		$sql = $this->generateTable();
		$this->addQuery($sql, $comment);

		return $this;
	}

	/**
	 * Add sql and comment in query's array
	 * @param string $sql
	 * @param string $comment
	 * @param string $option value of "use_passthru | begin_transaction"
	 * @return TC_Import_Model_Product_Populating_Query
	 */
	public function addQuery($sql = '', $comment = '', $option = ''){
		$this->_queries[] = array(
			'sql'  => $sql,
			'comment' => $comment,
			'option' => $option
		);

		return $this;
	}

	/**
	 * Add sql begin transaction
	 * @return TC_Import_Model_Product_Populating_Query
	 */
	public function addBeginTransaction(){
		$this->addQuery('', 'Begin transaction', 'begin_transaction');

		return $this;
	}

	/**
	 * Returns sql raw query to generate table
	 * @return string
	 */
	protected function generateTable(){
		$columns = $this->getConfig()->getColumns();
		$columns['magento_entity_id'] = array('type' => 'int');

		$sql = 'CREATE TABLE ' . $this->_table . '(';

		foreach ($columns as $name => $params) {
			$type = $this->getConfig()->getTypeForDatabase($params['type'], $name);
			$value = isset($params['default']) ? $params['default'] : 'NULL';
			if ($value != 'NULL') {
				$value = 'DEFAULT ' . $value;
			}
			$primary = isset($params['primary']) && $params['primary'] ? ' PRIMARY KEY' : '';

			$sql .= "\n" . $name . " " . $type . " " . $value . $primary . ",";
		}
		$sql = substr($sql, 0, -1) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8;' . "\n";

		return $sql;
	}

	/**
	 * Prepare LOAD DATA query from CSV
	 * @param string $file
	 * @return TC_Import_Model_Product_Populating_Query
	 */
	public function loadCSV($file){
		$file = str_replace('\\', '/', $file);
		$sql = "LOAD DATA INFILE '" . $file . "' INTO TABLE " . $this->_table . " FIELDS TERMINATED BY \"|\" ENCLOSED BY '\"' LINES TERMINATED BY \"\n\"";
		$sql .= " IGNORE 1 LINES \n";

		$fh = fopen($file, 'r');
		$columns = fgetcsv($fh, 0, '|', '"');
		fclose($fh);

		$sql .= '(' . implode(', ',$columns) . ');';

		$this->addQuery($sql, 'Import data from ' . $file . ' file');

		return $this;
	}

	/**
	 * Execute sql query with passthru php command
	 * @param string $sql
	 * @return TC_Import_Model_Product_Populating_Query
	 */
	protected function executePassthruQuery($sql){
		$config = Mage::getConfig()->getResourceConnectionConfig(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);

		$sql = str_replace('"', '\"', $sql);

		$cmd = 'mysql -u' . $config->username . ' -p' . $config->password . ' -h' . $config->host . ' ' . $config->dbname . ' -e "'.$sql.'"';

		$log = $this->getLoger();
		$log->log("Executing Request passthru :" . PHP_EOL . $cmd, 6);
		passthru($cmd);

		return $this;
	}

	/**
	 * Execute sql query with Magento sql connection
	 * @param string $sql
	 * @return TC_Import_Model_Product_Populating_Query
	 * @throws Zend_Db_Statement_Exception
	 */
	protected function executeQuery($sql){
		$log = $this->getLoger();

		$log->log("Executing Request :" . PHP_EOL . $sql, 6);
		$result = $this->getConnection()->query($sql);

		return $this;
	}

	/**
	 * Execute sql query with Magento sql connection and show results
	 * @param string $sql
	 * @return TC_Import_Model_Product_Populating_Query
	 * @throws Zend_Db_Statement_Exception
	 */
	protected function executeQueryShowResults($sql){
		$log = $this->getLoger();

		$result = false;
		$log->log("Executing Request :" . PHP_EOL . $sql, 6);
		$result = $this->getConnection()->query($sql);

		if (!$result) {
			$log->log("- Records changed:  None" . PHP_EOL, 6);
		} else {
			$log->log($result->rowCount()." rows affected by the execution of the last request", 6);
		}

		return $this;
	}

	/**
	 * Execute all queries in _queries array
	 * @return bool
	 */
	public function execute($file){
		$errorFound = false;

		$log = $this->getLoger();

		$query = current($this->_queries);
		$queryCount = count($this->_queries);

		while ($query !== false && $errorFound == false) {
			$log->log($query['comment'], 6);

			if ($query['option'] == 'begin_transaction') {
				$this->getConnection()->beginTransaction();
			} elseif($query['option'] == 'use_passthru') {
				$this->executePassthruQuery($query['sql']);
			} elseif($query['option'] == 'show_results') {
				$this->executeQueryShowResults($query['sql']);
			} else {
				try {
					$this->executeQuery($query['sql']);
				} catch (Zend_Db_Statement_Exception $e) {
					$log->log('Mysql Error Code is: ' . $e->getCode(), 2);
					$log->log("CallStack " . PHP_EOL . $e->getMessage(), 2);
					Mage::log('Mysql Error Code is: ' . $e->getCode(), 2, 'sqlerrors.log');
					Mage::log("CallStack " . PHP_EOL . $e->getMessage(), 2, 'sqlerrors.log');
					$errorFound = true;
				}
			}
			$log->log((1 + key($this->_queries)) . '/' . $queryCount . ' queries', 6);
			$log->log('SQL query', 6);
			$query = next($this->_queries);
		}

		if ($errorFound !== true) {
			$log->log('Import successful. Committing SQL queries...' . PHP_EOL, 6);
			$this->getConnection()->commit();
			TC_Import_Model_Product_Fs::markProcessed($file);
			$this->getConnection()->query('DROP TABLE IF EXISTS ' . $this->_table . ';');
		} else {
			$log->log('En error has occured. Rolling back SQL queries...' . PHP_EOL, 1);
			$this->getConnection()->rollback();
			$this->getConnection()->query('DROP TABLE IF EXISTS ' . $this->_table . ';');
			TC_Import_Model_Product_Fs::markError($file);
			die('ERROR');
		}

		return !$errorFound;
	}

	/**
	 * Return all sql queries
	 * @return string
	 */
	public function __toString(){
		$output = '';
		foreach ($this->_queries as $query){
			if (isset($query['sql'])){
				$output .= $query['sql'] . PHP_EOL . PHP_EOL;
			}
		}

		return $output;
	}

	/**
	 * Get current table name
	 * @return string
	 */
	public function getTableName(){
		return $this->_table;
	}

	/**
	 * Setter for Magento connection
	 * @param Varien_Db_Adapter_Interface $connection
	 */
	public function setConnection($connection){
		$this->_connection = $connection;

		return $this;
	}

	/**
	 * Getter for Magento connection
	 * @return Varien_Db_Adapter_Interface $connection
	 */
	public function getConnection(){
		return $this->_connection;
	}

	/**
	 * Setter for loger object
	 * @param TC_Import_Model_Loger_Abstract $_loger
	 */
	public function setLoger($_loger){
		$this->_loger = $_loger;

		return $this;
	}

	/**
	 * Getter for loger object
	 * @return TC_Import_Model_Loger_Abstract
	 */
	public function getLoger(){
		return $this->_loger;
	}

	/**
	 * Setter config model
	 * @param Zend_Config $config
	 */
	public function setConfig($config){
		$this->_config = $config;

		return $this;
	}

	/**
	 * Getter config model
	 * @return Zend_Config
	 */
	public function getConfig(){
		return $this->_config;
	}
}