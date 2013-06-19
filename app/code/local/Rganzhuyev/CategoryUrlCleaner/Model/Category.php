<?php
class Rganzhuyev_CategoryUrlCleaner_Model_Category extends Mage_Catalog_Model_Category {

    public function getUrl(){
        $parentUrl = parent::getUrl();
        $url = str_replace(Mage::getUrl(''), Mage::getUrl('').$this->getId().'-category-', $parentUrl);
        return $url;
    }
}