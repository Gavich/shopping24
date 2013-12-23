<?php
function rus2translit($string) {
    $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
    return strtr($string, $converter);
}
function str2url($str) {
    // переводим в транслит
    $str = rus2translit($str);
    // в нижний регистр
    $str = strtolower($str);
    // заменям все ненужное нам на "-"
    $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
    // удаляем начальные и конечные '-'
    $str = trim($str, "-");
    return $str;
}



require 'app/Mage.php';
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

Mage::app();

        for ($ind=3001;$ind<=24100;$ind++)
		{
		$category = Mage::getModel('catalog/category')->load($ind); // загружаем категорию с идентификатором 20 

		$categoryChild = $category->getChildren(); // получаем подкатегории как строку, id подкатегорий через запятую 
		$categoryChildArray = explode(',', $categoryChild); // делаем из строки массив 
		 //var_dump($categoryChildArray); // выведем массив с id подкатегорий  "url_path" name@
		//var_dump($category); // выведем массив с id подкатегорий
		$categorymagentoattributes = $category->getAttributes();
		$name=$categorymagentoattributes['name']->getFrontend()->getValue($category);
		$name_lin = mb_convert_encoding($name,'KOI8-R','UTF-8' );
		$ott_id=$categorymagentoattributes['original_id']->getFrontend()->getValue($category);
		$title=$categorymagentoattributes['meta_title']->getFrontend()->getValue($category);
		echo mb_convert_encoding($title,'KOI8-R','UTF-8' )."====";
		//$url_key=$categorymagentoattributes['title']->getFrontend()->getValue($category);
		/*echo $name."====";
		echo $name_lin."====";
		echo $ott_id."====";
		
		echo $url_key."====";
		*/
		$new_title=$name."  купить с доставкой по Украине. Лучшие цены и выбор.";
		
		echo "===".mb_convert_encoding($new_title,'KOI8-R','UTF-8' )."===";
		
		
		
		$general['meta_title'] = $new_title;
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
?>