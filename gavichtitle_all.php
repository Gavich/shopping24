<?php



require 'app/Mage.php';
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

Mage::app();

        for ($ind=1;$ind<=23362;$ind++)
		{
		$category = Mage::getModel('catalog/category')->load($ind); 
		$categorymagentoattributes = $category->getAttributes();
		$name=$categorymagentoattributes['name']->getFrontend()->getValue($category);
		if (($name!='') and strlen($name)>1)
		    {
				$name_lin = mb_convert_encoding($name,'KOI8-R','UTF-8' );
				$ott_id=$categorymagentoattributes['original_id']->getFrontend()->getValue($category);
				$title=$categorymagentoattributes['meta_title']->getFrontend()->getValue($category);
				$meta_keywords=$categorymagentoattributes['meta_keywords']->getFrontend()->getValue($category);
				$meta_description=$categorymagentoattributes['meta_description']->getFrontend()->getValue($category);
				$new_title=$name." купить с доставкой по Украине. Лучшие цены и выбор.";
				$name_lo=strtolower($name);
				echo '+++++++'.strtolower("ZZZZXXXXXXX").'+++++';
				$name_low=mb_convert_case($name_lo, MB_CASE_LOWER, "UTF-8");
				$name_fir=ucfirst($name_lo);
				$new_keywords="$name_low, $name_low купить, $name_low цена, $name_low интернет магазин, $name_low доставка по Украине, стоимость, каталог Отто, обувь и одежда";
				$new_description="Купить $name_low с доставкой по Украине. Прекрасные цены и огромный выбор. $name_fir от ведущих европейских и мировых производителей и брендов. Наш интернет-магазин предлагает широкий выбор $name_low.";
				echo "===".$new_keywords."===";		
				echo "===".$new_description."===";
				$new_title=$name."  купить с доставкой по Украине. Лучшие цены и выбор.";
				$general['meta_title'] = $new_title;
				$general['meta_keywords'] = $new_keywords;
				$general['meta_description'] = $new_description;
				$category->addData($general);
				try {
					$category->save();
					echo "Success! Id: ".$category->getId();
				}
				catch (Exception $e){
				echo "------------------------------------------------------------------------------------------------------";
				echo "------------------------------------------------------------------------------------------------------";
					echo $e->getMessage();
				}
			}	
		}
?>