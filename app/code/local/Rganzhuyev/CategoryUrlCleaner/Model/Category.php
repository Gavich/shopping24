<?php
class Rganzhuyev_CategoryUrlCleaner_Model_Category extends Mage_Catalog_Model_Category {

    public function getUrl(){
        $url = parent::getUrl();
        if (Mage::getStoreConfig('category_url_cleaner/general/enabled'))
        {
            $urlExploded = explode('/', $url);
            $url =  array_pop($urlExploded);
            $url = 'c'.str_replace('.html', '', $this->getId().'-'.$url);
            $url = Mage::getUrl($url);
        }
        return $url;
    }
}