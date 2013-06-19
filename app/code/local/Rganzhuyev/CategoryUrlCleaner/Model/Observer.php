<?php
class Rganzhuyev_CategoryUrlCleaner_Model_Observer extends Mage_Catalog_Model_Observer {

    public function categoryUrlClean(Varien_Event_Observer $observer){
        $request = Mage::app()->getRequest();
        $url = $request->getOriginalPathInfo();
        $path = explode('/', $url);
        $path = array_reverse($path);
        $categoryUrl = explode('-', $path[0]);
        $categoryId = array_shift($categoryUrl);
        $isCategory = @array_shift($categoryUrl) == 'category';
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