<?php
class Rganzhuyev_CategoryUrlCleaner_Model_Observer extends Mage_Catalog_Model_Observer {

    public function categoryUrlClean(Varien_Event_Observer $observer){
        $request = Mage::app()->getRequest();
        $url = $request->getOriginalPathInfo();
        if(substr($url, -1) == '/') {
            $url = substr($url, 0, strlen($url)-1);
        }
//        var_dump($url);
        $path = explode('/', $url);
        $path = array_reverse($path);
        $categoryUrl = explode('-', $path[0]);
        $category = array_shift($categoryUrl);
        $isCategory = substr($category, 0,1) == 'c';
        $categoryId = (int)substr($category, 1);
        $categoryPath = implode('-', $categoryUrl);
        $urlPath = Mage::getModel('core/url_rewrite')->loadByIdPath('category/'.$categoryId);

        $urlPath = $urlPath->getData('request_path');
//        var_dump($urlPath);
//        var_dump($categoryId);
//        var_dump(strpos($urlPath, $categoryPath));
//        var_dump($isCategory); var_dump($categoryPath); var_dump($categoryUrl); var_dump($path); die('HERE');
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