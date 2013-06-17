<?php
class Rganzhuyev_ReviewsSummary_Helper_Data extends Mage_Core_Helper_Abstract {

    protected $_reviewsHelperBlock;

    protected function _initReviewsHelperBlock()
    {
        if (!$this->_reviewsHelperBlock) {
            if (!Mage::helper('catalog')->isModuleEnabled('Mage_Review')) {
                return false;
            } else {
                $this->_reviewsHelperBlock = Mage::app()->getLayout()->createBlock('review/helper');
            }
        }

        return true;
    }

    public function getReviewsCount($review){
        if (!$this->_initReviewsHelperBlock()) {
            return '';
        }
        $collection = Mage::getModel('review/review')->getCollection()
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
            ->addEntityFilter('product', $review->getProduct()->getId())
            ->setDateOrder();
        return $collection->count();
    }
}