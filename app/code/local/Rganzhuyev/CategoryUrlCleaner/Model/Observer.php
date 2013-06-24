<?php
class Rganzhuyev_CategoryUrlCleaner_Model_Observer extends Mage_Catalog_Model_Observer {

    public function categoryUrlClean(Varien_Event_Observer $observer){
        if (Mage::getStoreConfig('category_url_cleaner/general/enabled'))
        {
            $request = Mage::app()->getRequest();
            $url = $request->getOriginalPathInfo();
            if(substr($url, -1) == '/') {
                $url = substr($url, 0, strlen($url)-1);
            }

            $path = explode('/', $url);
            $path = array_reverse($path);
            $categoryUrl = explode('-', $path[0]);
            $category = array_shift($categoryUrl);
            $isCategory = substr($category, 0,1) == 'c';
            $categoryId = (int)substr($category, 1);
            $categoryPath = implode('-', $categoryUrl);
            $urlPath = Mage::getModel('core/url_rewrite')->loadByIdPath('category/'.$categoryId);

            $urlPath = $urlPath->getData('request_path');

            if ($isCategory && $urlPath && strpos($urlPath, $categoryPath) !== false) {
                $moduleName = 'catalog';
                $controllerName = 'category';
                $actionName = 'view';
                $request->setModuleName($moduleName)
                    ->setControllerName($controllerName)
                    ->setActionName($actionName)
                    ->setParam('id', $categoryId);
                $observer->getFront()->setRequest($request);
            }
        }
    }
}