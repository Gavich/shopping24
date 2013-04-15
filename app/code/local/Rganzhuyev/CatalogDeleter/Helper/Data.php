<?php
class Rganzhuyev_CatalogDeleter_Helper_Data extends Mage_Core_Helper_Abstract {

    public function removeChildrenCategories($parentCategoryId) {
        $levels = '';
        $categoriesIerarchy = array();
        $excludedCategories = array();

        try {
            $category = Mage::getModel('catalog/category')->load($parentCategoryId);
            /* var $category Mage_Catalog_Model_Category*/
            $childrenCategories = $category->getAllChildren(true);
            $childrenCategories = Mage::getModel('catalog/category')->getCollection()
                ->addFieldToFilter('entity_id', array('in'=>$childrenCategories))
                ->addAttributeToSelect('name');

            foreach($childrenCategories as $child) {
                if($child->getId() != $parentCategoryId) {
                    $categoriesIerarchy[$child->getLevel()][] = $child;
                }
            }
            for(;!empty($categoriesIerarchy);) {
                $categoriesLevel = array_pop($categoriesIerarchy);
                for($categoryIndex=0; $categoryIndex<count($categoriesLevel); $categoryIndex++) {
                    $category = $categoriesLevel[$categoryIndex];
                    if($category->getProductCount() > 0 || in_array($category->getId(), $excludedCategories)) {
                        $excludedCategories[$category->getId()] = $category->getId();
                        $excludedCategories[$category->getParentId()] = $category->getParentId();
                    }

                    $categoryId = $category->getId();
                    if(!in_array($categoryId, $excludedCategories)) {
//                        $levels .= $category->getName() . '<br />';
                        $category->delete();
                    }
                    unset($excludedCategories[$categoryId]);
                }
            }
//            Mage::throwException($levels);
        }
        catch (Mage_Core_Exception $e){
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::app()->getResponse()->setRedirect(Mage::getUrl('*/catalog_category/edit', array('_current'=>true)));
            return;
        }
        catch (Exception $e){
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('An error occurred while trying to delete the category.'));
            Mage::app()->getResponse()->setRedirect(Mage::getUrl('*/catalog_category/edit', array('_current'=>true)));
            return;
        }
    }

    public function removeProductsFromCategoryAndChildren($categoryId) {
        try {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            /* var $category Mage_Catalog_Model_Category*/
            $childrenCategories = $category->getAllChildren(true);
            foreach($childrenCategories as $childId) {
                $child = Mage::getModel('catalog/category')->load($childId);
                if($child && $child->getProductCount() > 0) {
                    $productCollection = $child->getProductCollection();
                    foreach($productCollection as $product) {
                        $product->delete();
                    }
                }
            }
        }
        catch (Mage_Core_Exception $e){
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->getResponse()->setRedirect($this->getUrl('*/catalog_category/edit', array('_current'=>true)));
            return;
        }
        catch (Exception $e){
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('An error occurred while trying to delete the category.'));
            $this->getResponse()->setRedirect($this->getUrl('*/catalog_category/edit', array('_current'=>true)));
            return;
        }
    }
}