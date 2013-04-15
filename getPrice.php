<?php

require 'app/Mage.php';
//Mage::setIsDeveloperMode(true);
//ini_set('display_errors', 1);
//error_reporting(E_ALL | E_STRICT);

Mage::app();

$request = Mage::app()->getRequest();

try {
	if ($request->isXmlHttpRequest()){
		$id = $request->getParam('id', false);
		$storeId = $request->getParam('store_id', false);
		if ($id === false || $storeId === false){
			throw new Exception('Wrong data specified');
		}

		/** @var $result TC_Import_Model_Updater */
		$result = Mage::getModel('tcimport/updater')->getDimensionsArray($id, $storeId);

		echo Zend_Json::encode($result);
	} else {
		throw new Exception('Only ajax requests allowed.');
	}
}catch (Exception $e){
	$message = __FILE__ . ': Error ---------------' . PHP_EOL . $e->getMessage();
	Mage::log($message, 1, 'get_price.log');
	die('');
}