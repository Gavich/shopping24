<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Product_Csv extends Varien_Object{

	protected $_adapter = null;

	/**
	 * Write data into file
	 * @param  string $filename
	 * @return /TC_Import_Model_Product_Csv
	 */
	public function write($filename){
		if ($this->getPath() == null){
			throw new Exception('Path is not specified');
		}

		if ($this->getProducts() == null){
			throw new Exception('Nothing to write');
		}

		$headers = $this->_prepareHeaders();
		if (in_array('id_field', $headers)){
			unset($headers[array_search('id_field', $headers)]);
		}

		$fullPath = $this->getPath() . DS . $filename . '.csv';
		if (file_exists($fullPath)){
			throw new Exception('File already exists');
			// unlink($fullPath);
		}

		$fh = fopen($fullPath, 'w+');

		//write headers
		$this->getAdapter()->fputcsv($fh, $headers, '|');

		TC_Import_Model_Converter_Main::convert($this->getProducts(), $this->getConfig(), $this);
		foreach ($this->getProducts() as $product) {
			/*if(count($this->getProducts()) == 1){
				//seems that it's simple
				$product['type'] = 'simple';
			}*/
			$toWrite = array();
			foreach ($headers as $key => $value) {
				if (isset($product[$value])){
					$toWrite[$key] = str_replace(array('|', '"'), '', $product[$value]);
				}else{
					$toWrite[$key] = '';
				}
			}
			$this->getAdapter()->fputcsv($fh, $toWrite, '|');
		}

		fclose($fh);

		return $this;
	}

	/**
	 * Cleaning data for next iteration
	 * @return /TC_Import_Model_Product_Csv
	 */
	public function clear(){
		$this->unsProducts();

		return $this;
	}

	/**
	 * Preparing file headers and sabing array to local variable
	 * extracted data only one time
	 * @return array
	 */
	protected function _prepareHeaders(){
		$keyGlobal = array();
		foreach ($this->getProducts() as $product) {
			$keys = array_keys($product);
			foreach ($keys as $key) {
				if(!in_array($key, $keyGlobal)){
					$keyGlobal[] = $key;
				}
			}
		}

		$required = $this->getConfig()->getCsvRequired();
		foreach ((array)$required as $key => $value) {
			if(!in_array($key, $keyGlobal)){
				$keyGlobal[] = $key;
			}
		}

		return $keyGlobal;
	}

	/**
	 * Checks if write adapter is not exists in protected variable and returns it
	 * @return Varien_File_Csv
	 */
	protected function getAdapter(){
		if (is_null($this->_adapter)){
			$this->_adapter = new Varien_File_Csv();
		}

		return $this->_adapter;
	}
}