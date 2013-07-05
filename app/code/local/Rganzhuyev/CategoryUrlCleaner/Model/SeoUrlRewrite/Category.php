<?php
class Rganzhuyev_CategoryUrlCleaner_Model_SeoUrlRewrite_Category extends Mage_Sitemap_Model_Resource_Catalog_Category {

    protected function _prepareCategory(array $categoryRow)
    {
        $category = parent::_prepareCategory($categoryRow);
        $categoryUrl = $category->getUrl();
        if (strpos($categoryUrl, 'catalog/category/view/id') === false){
            $categoryUrl = 'c'.$category->getId().'-'.preg_replace('#.html$#', '/', $categoryUrl);
            $category->setUrl($categoryUrl);
        }
        return $category;
    }
}