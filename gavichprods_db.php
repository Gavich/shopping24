<?php
$host="88.198.10.242";
$user="root";
$pwd="AwnQtG";
$db=mysql_connect($host,$user,$pwd);
mysql_set_charset("utf8",$db);
mysql_select_db("parser_otto",$db);
$tabl_name="artik_01";
$que_creat="CREATE TABLE $tabl_name (otto_id VARCHAR(200));";
$send=mysql_query($que_creat);
echo mysql_error();
require 'app/Mage.php';
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);
Mage::app();
        for ($ind=1233181;$ind<=1543610;$ind++) //
		{
		$product = Mage::getModel('catalog/product')->load($ind); 
		$productmagentoattributes = $product->getAttributes();
		$type=$productmagentoattributes['type_id']->getFrontend()->getValue($product);
		$sku=$productmagentoattributes['sku']->getFrontend()->getValue($product);

		if (($type!='simple') and ($sku!=''))
				{
				$que_creat="INSERT INTO $tabl_name (otto_id) VALUES ('$sku');";
				//echo $que_creat.'===';
				$send=mysql_query($que_creat);
				echo mysql_error();
				}
		}
?>