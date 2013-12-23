<?php



require 'app/Mage.php';
/*
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);
*/
Mage::register('isSecureArea', 1);
Mage::app();

        for ($ind=1;$ind<=1;$ind++)
		{
		$category = Mage::getModel('catalog/category')->load($ind); // загружаем категорию с идентификатором 20 
		
		try {
			$category->delete();
			echo "Success! Id: ".$category->getId();
		}
		catch (Exception $e){
		echo "------------------------------------------------------------------------------------------------------";
		echo "------------------------------------------------------------------------------------------------------";
			echo $e->getMessage();
		}
		}
?>