<?php


require 'app/Mage.php';
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

Mage::app();
$fp = fopen("gavichprods.txt", "a");
        for ($ind=1218528;$ind<=1233180;$ind++)
		{
		$product = Mage::getModel('catalog/product')->load($ind); // загружаем категорию с идентификатором 20 
		 //var_dump($categoryChildArray); // выведем массив с id подкатегорий  "url_path" name@
		 //var_dump($product); 
		$productmagentoattributes = $product->getAttributes();
		$type=$productmagentoattributes['type_id']->getFrontend()->getValue($product);
		$sku=$productmagentoattributes['sku']->getFrontend()->getValue($product);
		
		//echo $type."====";
		//echo $sku."====";
		if ($type!='simple')
				{
				 // Открываем файл в режиме записи
				$test = fwrite($fp, $sku.';'); // Запись в файл
			//	if ($test) echo  mb_convert_encoding('Данные в файл успешно занесены.','KOI8-R','UTF-8' );
			//	else echo mb_convert_encoding('Ошибка при записи в файл.','KOI8-R','UTF-8' );
				
				}

		}
fclose($fp); //Закрытие файла	
?>