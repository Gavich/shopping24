<?php
class Rganzhuyev_CategoryUrlCleaner_Model_Category extends Mage_Catalog_Model_Category {

    public function getUrl(){
        $parentUrl = parent::getUrl();
        $urlExploded = explode('/', $parentUrl);
        $url =  array_pop($urlExploded);
        $url = 'c'.str_replace('.html', '', $this->getId().'-'.$url);
        $url = Mage::getUrl($url);
//        $url = str_replace(Mage::getUrl(''), Mage::getUrl('').$this->getId().'-category-', $parentUrl);
        return $url;
    }
}