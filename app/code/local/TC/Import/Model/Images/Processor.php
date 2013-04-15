<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Images_Processor extends TC_Import_Model_Processor{

	private $_imageDir = null;
	protected $_count = null;
	protected $_offset = null;

	const SEPARATOR = ',';

	/**
	 * Get prefix for loger storage name
	 * @return  string
	 */
	public function getLogPrefix(){
		return 'images';
	}

	/**
	 * Run process
	 * @return  bool
	 */
	public function run(){
		$this->log('Process started');

		$this->_beforeRun();


		$entityType = Mage::getModel('eav/entity_type')->loadByCode('catalog_product');
		$initImageId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','init_image');
		$initImage = Mage::getModel('catalog/resource_eav_attribute')->load($initImageId);

		$mainImageId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','main_image');
		$mainImage = Mage::getModel('catalog/resource_eav_attribute')->load($mainImageId);

		$isPhotoProcessedId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','is_photo_processed');
		$isPhotoProcessed = Mage::getModel('catalog/resource_eav_attribute')->load($isPhotoProcessedId);

		/*$originalId__id = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','original_id');
		$originalId = Mage::getModel('catalog/resource_eav_attribute')->load($originalId__id);*/

		$imageId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','image');

		$smallImageId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','small_image');

		$thumbId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','thumbnail');

		$mediaGalleryId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','media_gallery');

		$optId = 0;
		$doneOpt = 0;
		foreach ((array)$isPhotoProcessed->getSource()->getAllOptions(true) as $value) {
			if ($value['label'] == 'Needed'){
				$optId = $value['value'];
			}elseif($value['label'] == 'Done'){
				$doneOpt = $value['value'];
			}
		}
		$resource = Mage::getModel('core/resource');
		$productIds = $this->getAdapter()->select()
			->from(array('e' => $resource->getTableName('catalog/product')), array('entity_id', 'sku'))
			->join(array('ii' => $initImage->getBackendTable()), 'ii.entity_id = e.entity_id AND ii.attribute_id=' . $initImageId, array('init_image' => 'ii.value'))
			/*->join(array('oid' => $originalId->getBackendTable()), 'oid.entity_id = e.entity_id AND oid.attribute_id=' . $originalId__id, array('original_id' => 'oid.value'))*/
			->join(array('ipp' => $isPhotoProcessed->getBackendTable()), 'ipp.entity_id = e.entity_id AND ipp.value=' . $optId . ' AND ipp.attribute_id =' . $isPhotoProcessedId, array())
			->joinLeft(array('mi' => $mainImage->getBackendTable()), 'mi.entity_id = e.entity_id AND mi.attribute_id=' . $mainImageId, array('main_image' => 'mi.value'))
			->joinLeft(array('l' => $resource->getTableName('catalog_product_relation')), 'l.child_id = e.entity_id', array())
			->joinLeft(array('p' => $resource->getTableName('catalog/product')), 'l.parent_id = p.entity_id', array())
			->limit($this->_count, $this->_offset);

        $productIds->columns(array('sku_folder' => new Zend_Db_Expr("IF(p.sku IS NOT NULL, p.sku, e.sku)")));
		$productIds = $this->getAdapter()->fetchAll($productIds);

		$media = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product';
		foreach ($productIds as $product) {
			if (empty($product['init_image'])){
				continue;
			}

			$initImages = explode(self::SEPARATOR, $product['init_image']);

			$mainImages = explode(self::SEPARATOR, $product['main_image']);
			$mainImages = array_slice($mainImages, 0, 1);
			$mainImages = isset($mainImages[0]) ? $mainImages[0] : '';

			$searchKey = array_search($mainImages, $initImages);
			if ($searchKey !== false && $mainImages != ''){
				$mainI = $initImages[$searchKey];
				unset($initImages[$searchKey]);
				$initImages = array_slice($initImages, 0, 5);
				$initImages[] = $mainI;
			}else{
				$initImages = array_slice($initImages, 0, 6);
			}

			if (empty($initImages)){
				continue;
			}

			$folder1 = substr($product['sku'], 0, 1);
			$folder2 = substr($product['sku'], 1, 1);
			$dir = DS . $folder1 . DS . $folder2;

			try{
				$this->getAdapter()->beginTransaction();
				foreach ($initImages as $key => $value) {
					if ($key == 0 && !in_array($mainImages, $initImages)){
						$mainImages = $value;
					}
					$fullPath = $this->_getImageDir($product['sku_folder']) . DS . $value;
					if (@file_exists($fullPath)){
						if (!@is_dir($media . DS . $dir)){
							mkdir($media . DS . $dir, 0777, true);
						}
						$info = pathinfo($fullPath);

						$imagePath = $media . DS . $dir . DS . $info['basename'];
						copy($fullPath, $imagePath);

						$toInsert = array(
							'attribute_id' => $mediaGalleryId,
							'entity_id' => $product['entity_id'],
							'value' => $dir . DS . $info['basename']
						);

						$this->getAdapter()->insertOnDuplicate($resource->getTableName('catalog_product_entity_media_gallery'), $toInsert, array('value'));

						$subselect = "
							(
								SELECT value_id FROM " . $resource->getTableName('catalog_product_entity_media_gallery') . " WHERE entity_id = " . $product['entity_id'] . "
								AND value = '" . $dir . DS . $info['basename'] . "' AND attribute_id = " . $mediaGalleryId . " limit 1
							)
						";
						$pos = $value == $mainImages ? 0 : 1;
						$this->getAdapter()->query("
							INSERT INTO " . $resource->getTableName('catalog_product_entity_media_gallery_value') . " SET store_id = " . Mage_Core_Model_App::ADMIN_STORE_ID . "
							, position = " . $pos . ", disabled = 0, value_id = " . $subselect . "
							ON DUPLICATE KEY UPDATE value_id = " . $subselect . "
						");
						if($value == $mainImages){
							$insert = array(
								'attribute_id' => $imageId,
								'store_id' => Mage_Core_Model_App::ADMIN_STORE_ID,
								'entity_id' => $product['entity_id'],
								'value' => $dir . DS . $info['basename'],
								'entity_type_id' => $entityType->getId()
							);

							$this->getAdapter()->insertOnDuplicate($resource->getTableName('catalog_product_entity_varchar'), $insert, array('value'));

							//thumb
							$insert['attribute_id'] = $thumbId;
							$this->getAdapter()->insertOnDuplicate($resource->getTableName('catalog_product_entity_varchar'), $insert, array('value'));
							//small
							$insert['attribute_id'] = $smallImageId;
							$this->getAdapter()->insertOnDuplicate($resource->getTableName('catalog_product_entity_varchar'), $insert, array('value'));
						}
					}
				}
				$updateAttributeIPP = array(
					'attribute_id' => $isPhotoProcessedId,
					'store_id' => Mage_Core_Model_App::ADMIN_STORE_ID,
					'entity_id' => $product['entity_id'],
					'value' => $doneOpt,
					'entity_type_id' => $entityType->getId()
				);

				$this->getAdapter()->insertOnDuplicate($isPhotoProcessed->getBackendTable(), $updateAttributeIPP, array('value'));
				$this->getAdapter()->commit();
			}catch(Exception $e){
				$this->log($e->getMessage(), 2);
				$this->getAdapter()->rollback();
			}
		}

		$this->log('Reindex finished. SUCCESS!');
		return true;
	}


	/**
	 * Executed before main process run
	 * @return TC_Import_Model_Images_Processor
	 */
	private function _beforeRun(){
		$logInstance = Mage::getModel('tcimport/loger_file')
											->setProcess($this);

		$this->setAdapter(Mage::getModel('core/resource')->getConnection('core_write'))
			->setLoger($logInstance);

		$varDir = Mage::getBaseDir('var');

		if (!is_writable($varDir)){
			$this->_terminateCriticalError('VAR dir is not writeable');
		}

		//creating required directoires
		$workingDir = $varDir . DS . TC_Import_Model_Product_Processor::$_workingDir;
		foreach (TC_Import_Model_Product_Processor::$_dirs as $dir){
			$fullPath = $workingDir . DS . $dir;
			if(!is_dir($fullPath)){
				mkdir($fullPath, 0777, true);
			}
		}

		Varien_Profiler::enable();

		return $this;
	}

	/**
	 * Returns image source directory
	 * @return string
	 */
	protected function _getImageDir($originalId){
		$this->_imageDir = TC_Import_Model_Product_Processor::getPath('IMAGES') . DS . $originalId;

		return $this->_imageDir;
	}

	/**
	 * Sets limit
	 * @param null $count
	 * @param null $offset
	 * @return TC_Import_Model_Images_Processor
	 */
	public function setLimit($count = null, $offset = null){
		$this->_count = $count;
		$this->_offset = $offset;

		return $this;
	}
}
