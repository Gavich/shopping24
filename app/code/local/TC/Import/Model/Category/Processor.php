<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Category_Processor extends TC_Import_Model_Processor{

	/**
	 * Get prefix for loger storage name
	 * @return  string
	 */
	public function getLogPrefix(){
		return 'categories';
	}

	/**
	 * Run process
	 * @return  bool
	 */
	public function run(){
		$this->log('Process started');

		$defaultStore = Mage::app()->getWebsite(true)->getDefaultStore();

		// Needed for correct creation of categories url rewrites.
		$processes = Mage::getSingleton('index/indexer')->getProcessesCollection();
		$processes->walk('setMode', array(Mage_Index_Model_Process::MODE_MANUAL));
		$processes->walk('save');

		$rootCategory = Mage::getModel('catalog/category')->load($this->_getCatIdFromOrigId('start'))
						->setOriginalId('start');
		$rootCategory->save();
		try{
			$this->processChildren($rootCategory, $defaultStore);
		}catch(Exception $e){
			$this->log($e->getMessage(), 2);
			return false;
		}

		$this->log('Creating and updating categories finished. Starting reindex...');
		$processes->walk('setMode', array(Mage_Index_Model_Process::MODE_REAL_TIME));
		$processes->walk('save');

		// $processes->walk('reindexAll');

		$this->log('Reindex finished. SUCCESS!');
		return true;
	}

	/**
	 * Recursive function to process all categories tree
	 * @param  Mage_Catalog_Model_Category $categoryParent 
	 * @param  Mage_Core_Model_Store $store
	 * @return void(0)
	 */
	public function processChildren(Mage_Catalog_Model_Category $categoryParent, $store){
		//getting categories from external DB
		$children = $this->_getCatsByParent($categoryParent->getOriginalId());

		// echo count($children) . PHP_EOL;
		if (count($children) == 0){
			$this->log('Children not found for category: ' . $categoryParent->getOriginalId());
			return true;
		}

		foreach ((array)$children as $cat){
			//getMagento ID from attribute
			$idInMagento = $this->_getCatIdFromOrigId($cat['cat_id']);
			if (!$idInMagento){
				//trying to create
				$category = $this->_createCategory($categoryParent->getId(), $cat['cat_name'], $cat['cat_id'], $store);
			}else{
				//trying to update
				$category = $this->_updateCategory(
					$this->_getCatIdFromOrigId($cat['cat_id']),
					$categoryParent->getId(),
					$cat['cat_name']
				);
			}

			//processing children
			$this->processChildren($category, $store);
		}
	}

	/**
	 * Getting categories from external DB by parent_id column
	 * @param  string $parent
	 * @return Array()
	 */
	private function _getCatsByParent($parent){
		$this->log('Getting categories for parent: ' . $parent);
		$connection = $this->getAdapter();
		$select = $connection
			->select()
			->from('cats')
			->where('parent_id = ?', $parent);
		// echo $select . PHP_EOL;
		$catsToCheck = $connection->fetchAll($select);
		$this->log('Total categories received: ' . count($catsToCheck));

		return $catsToCheck;
	}

	/**
	 * Checks if changes occured in external DB and if they exist then updates category
	 * @param  int $id [Magento category ID]
	 * @param  int $parentCategory [Magento parent category ID]
	 * @param  string $name
	 * @return Mage_Catalog_Model_Category
	 */
	private function _updateCategory($id, $parentCategory, $name){
		$this->log('Updating category: ' . $name);
		$category = Mage::getModel('catalog/category')->load($id);

		if ($category->getName() != $name)
			$category->setName($name);

		if ($category->getParentId() != $parentCategory){
			$parentCategory = Mage::getModel('catalog/category')->load($parentCategory);
			$category
				->setParentId($parentCategory->getId())
				->setPath($parentCategory->getPath);
		}

		//save will not send query to DB if changes not occured
		$category->save();

		$this->log('Category has been updated: ' . $name);
		return $category;
	}

	/**
	 * Creating new category
	 * @param  Mage_Catalog_Category | int $parentCategory
	 * @param  string $name
	 * @param  string $origId
	 * @param  Mage_Core_Model_Store $store
	 * @return Mage_Catalog_Category
	 */
	private function _createCategory($parentCategory, $name, $origId, $store){
		$this->log('Creating category: ' . $name);
		$category = Mage::getModel('catalog/category');

		if (!$parentCategory instanceOf Mage_Catalog_Category){
			$parentCategory = Mage::getModel('catalog/category')->load($parentCategory);
		}
		$parentCategoryId = $parentCategory ->getId();
		$category
			->setData($this->_getDefaultCatData($name, $origId, $parentCategoryId))
			->setAttributeSetId($category->getDefaultAttributeSetId())
			->setStoreId($store->getId())
			->setPath($parentCategory->getPath())
			->save();

		$this->log('Category created, ID: ' . $category->getId());
		return $category;
	}

	/**
	 * Returns magento category ID or false
	 * @param  string $origId
	 * @return int | false
	 */
	private function _getCatIdFromOrigId($origId){
		if($origId == 'start'){
			$defaultStore = Mage::app()->getWebsite(true)->getDefaultStore();
			$defaultWebsiteRootCatId = $defaultStore->getRootCategoryId();

			return $defaultWebsiteRootCatId;
		}
		$coreResource = Mage::getModel('core/resource');
		$coreConnection = $coreResource->getConnection('core_read');

		$origIDAttributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_category','original_id');
		$origIDAttribute = Mage::getModel('catalog/resource_eav_attribute')->load($origIDAttributeId);

		$select = $coreConnection->select()
			->from(array('cc' => $coreResource->getTableName('catalog/category')), array('cc.entity_id'))
			->join(array('a' => $origIDAttribute->getBackendTable()), 'a.entity_id=cc.entity_id')
			->where('a.attribute_id =?', $origIDAttributeId)
			->where('a.value = ?', $origId);

		$result = $coreConnection->fetchOne($select);

		return $result;
	}

	/**
	 * Returns array with required category data
	 * @param  string $name
	 * @param  string $origId
	 * @param  int $parentId
	 * @return Array()
	 */
	private function _getDefaultCatData($name, $origId, $parentId){
		return array(
			'name'              => trim($name),
			'is_active'         => 1,
			'include_in_menu'   => 1,
			'is_anchor'         => 1,
			'url_key'           => '',
			'description'       => '',
			'parent_id'         => $parentId,
			'original_id'       => $origId
		);
	}
}