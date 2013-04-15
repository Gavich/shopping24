<?php
//@todo implement autoreload category tree
class Rganzhuyev_CatalogDeleter_RemoveController extends Mage_Adminhtml_Controller_Action {



    public function deleteCategoryTreeAction() {
        if ($categoryId = (int) $this->getRequest()->getParam('id')) {
            Mage::helper('catalogDeleter')->removeChildrenCategories($categoryId);
        }
        $this->getResponse()->setRedirect($this->getUrl('*/catalog_category/edit', array('_current'=>true)));
    }

    public function deleteProductTreeAction() {
        if ($categoryId = (int) $this->getRequest()->getParam('id')) {
            Mage::helper('catalogDeleter')->removeProductsFromCategoryAndChildren($categoryId);
        }
        $this->getResponse()->setRedirect($this->getUrl('*/catalog_category/edit', array('_current'=>true)));
    }
}