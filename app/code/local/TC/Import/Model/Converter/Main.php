<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Converter_Main{

	public static $_defaultStore = null;
	public static $_defaultPhotoAttribute = null;
	public static $_categories = null;

	public static function convert($products, $config, Varien_Object $toReturn){
		//filter data
		foreach ($products as &$product) {
			foreach ($product as $key => &$value) {
				$value = strip_tags($value);
				$value = trim($value);
			}
		}

		//trying to find configurable_attributes for configurable products
		//and specify sku for simple products
		//also adding category id for configurables
		TC_Import_Model_Converter_Main::fillConfigurableAttributes($products, $config);

		foreach ($products as &$product) {
			$defaults = $config->getCsvRequired();
			//Filling default values and if old_price exist we switching old_price with price because old_price means special price
			TC_Import_Model_Converter_Main::fillDefaults($product, $defaults);
		}

		$toReturn->setProducts($products);
	}

	public static function fillConfigurableAttributes(&$products, $config){
		$attrs = array();
		$images = array();
//		$main = '';
		$price = 0;

		$canConfigure = $config->getCsvConfigurableAttributes();
		foreach ($products as &$product) {
			if ($product['type'] == 'simple'){
				foreach ($canConfigure as $attr) {
					if(
							isset($product[$attr])
							&& !empty($product[$attr])
						){
						$attrs[] = $attr;
//						$product['sku'] = $product['sku'] . '_' . $product[$attr];
					}
				}
//				$product['sku'] = preg_replace('#[^a-z0-9_]#Usi', '', $product['sku']);
				$product['association_id'] = $products[0]['sku'];
				$product['name'] = $products[0]['name'];

				$imagesArray = explode(TC_Import_Model_Images_Processor::SEPARATOR, $product['init_image']);
				$images = array_merge($images, $imagesArray);
//				if (empty($main) && !empty($product['main_image'])){
//					$main = $product['main_image'];
//				}
				if (!$price){
					$price = $product['price'];
				}
			}else{
				$catIds = array();
				$cats = explode(',', $product['cat_id']);
				if (count($cats) == 0){
					$product['cat_id'] = '';
				}else{
					$cats = array_unique($cats);
					foreach ($cats as $cat) {
						$id = TC_Import_Model_Converter_Main::getCatIdByCode($cat);
						if (!empty($id)){
							$catIds[] = $id;
						}
					}
				}
				if (count ($catIds) > 0){
					$product['cat_id'] = implode(',', $catIds);
				}else{
					$product['cat_id'] = '';
				}

				$product['url_key'] = preg_replace('#[^a-z0-9]#Usi', '', $product['original_id']);
			}
		}

		if (!empty($attrs)){
			$attrs = array_unique($attrs);
			//0 always configurable
			$products[0]['configurable_attributes'] = implode(',', $attrs);
		}

		if (!empty($images)){
			$images = array_unique($images);
			if ($key = array_search('', $images)){
				unset($images[$key]);
			}
			$products[0]['init_image'] = implode(TC_Import_Model_Images_Processor::SEPARATOR, $images);
//			$products[0]['main_image'] = $main;
		}else{
			$products[0]['init_image'] = '';
//			$products[0]['main_image'] = '';
		}

		if (!isset($products[0]['price']) || empty($products[0]['price'])){
			$products[0]['price'] = $price;
		}
		return $products;
	}

	public static function fillDefaults(&$product, $fields){
		foreach ($fields as $field => $data) {
			if(!isset($product[$field])){
				if(isset($data['default'])){
					$product[$field] = $data['default'];
				}else{
					$product[$field] = '';
				}
			}

			switch ($field) {
				case 'is_photo_processed':
					$product[$field] = TC_Import_Model_Converter_Main::getDefaultPhotoAttribute();
					break;
				case 'visibility':
					$product[$field] = $product['type'] == 'simple'
										? Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
										: Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;
					break;
			}
		}

		if(isset($product['special_price']) && !empty($product['special_price']) && $product['special_price'] > 0){
			$regularPrice = $product['special_price'];
			$product['special_price'] = $product['price'];
			$product['price'] = $regularPrice;
		}elseif (isset($product['special_price']) && $product['special_price'] == 0){
			unset($product['special_price']);
		}
	}

	public static function getDefaultPhotoAttribute(){
		if (TC_Import_Model_Converter_Main::$_defaultPhotoAttribute == null){
			$attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'is_photo_processed');
			if ($attribute->usesSource()) {
				$options = $attribute->getSource()->getAllOptions(false);
				foreach ($options as $key => $value) {
					if ($value['label'] == 'Needed'){
						TC_Import_Model_Converter_Main::$_defaultPhotoAttribute = $value['value'];
					}
				}
			}
		}

		return TC_Import_Model_Converter_Main::$_defaultPhotoAttribute;
	}

	public static function getCatIdByCode($code){
		if (TC_Import_Model_Converter_Main::$_categories == null){
			$coreResource = Mage::getModel('core/resource');
			$coreConnection = $coreResource->getConnection('core_read');

			$origIDAttributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_category','original_id');
			$origIDAttribute = Mage::getModel('catalog/resource_eav_attribute')->load($origIDAttributeId);

			$select = $coreConnection->select()
				->from(array('cc' => $coreResource->getTableName('catalog/category')), array('cc.entity_id'))
				->join(array('a' => $origIDAttribute->getBackendTable()), 'a.entity_id=cc.entity_id', 'value')
				->where('a.attribute_id =?', $origIDAttributeId);

			TC_Import_Model_Converter_Main::$_categories = $coreConnection->fetchPairs($select);
		}

		$id = array_search($code, TC_Import_Model_Converter_Main::$_categories);
		if ($id === false){
			return '';
		}else{
			return $id;
		}
	}
}
