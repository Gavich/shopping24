<?php

class Itdelight_Metadata_Model_Observer {
    
    public function _construct()
    {
        
    }
     public function applyToCategoriesOfField($category,$model){
            
            if($model->getCatForm()){
                $categories=$model->getCategories();
                
                $arr=explode(',',$categories);
                foreach($arr as $item){
                    $item=trim($item);
                     
                    if($item == $category->getId()){
                         
                        $this->setCategoryMetadata($category,$model);
                       
                    }
                }
            }
        }
    public function applyToCategoryAndChild($category,$model){
            if($model->getCatChild()){
                //Mage::Log($parents,null,'catya.log');
                $parents=$category->getParentIds();
                
                foreach($parents as $parent){
                if(($model->getCategoryId()==$category->getId())OR($model->getCategoryId()==$parent)){
                    
                    $this->setCategoryMetadata($category,$model);
                }
            }
            }
        }
    
        public function applyToCategory($category,$model){
            if($model->getCat()){
                if($model->getCategoryId()==$category->getId()){
                    
                    $this->setCategoryMetadata($category,$model);
                }
            }
            
        }
        public function applyToProductsOfField($product,$model){
            
            if($model->getProdForm()){
                $products=$model->getProducts();
                
                $arr=explode(',',$products);
                foreach($arr as $item){
                    $item=trim($item);
                     
                    if($item == $product->getId()){
                         
                        $this->setProductMetadata($product,$model);
                       
                    }
                }
            }
        }
        public function generateForProductsOfCatandChild($product,$model){
         
        if($model->getProdChildcat()){
            $categories=$product->getCategoryIds();
            
            foreach($categories as $item){
                $category=Mage::getModel('catalog/category')->load($item);
                $parents=$category->getParentIds();
               foreach($parents as $parent){
               if(($model->getCategoryId()==$item)OR($model->getCategoryId()==$parent)){
                $this->setProductMetadata($product,$model); 
            }
               }
            }
            
        }
    }
    public function generateForProductsOfCat($product,$model){
         
        if($model->getProdCat()){
            $categories=$product->getCategoryIds();
            foreach($categories as $item){
               if($model->getCategoryId()==$item){
                $this->setProductMetadata($product,$model); 
            }
            }
            
        }
    }
       public function generateNewMetadataCat($product,$custom){
         $page=Mage::getSingleton('core/session')->getPage($page);
        $array=explode("<e>",$custom);
        $count=substr_count($custom,"<e>");
        if(($page>$count)&($count>0)){
            $new_index=$page % $count;
            $new_data=$array[$new_index];
        }else{
            $new_data=$product.' '.$array[$page-1];
       
            
        }
        
        return $new_data;
    }
    public function generateNewMetadata($product,$custom){
         $page=Mage::getSingleton('core/session')->getPage($page);
        $array=explode("<e>",$custom);
        $count=substr_count($custom,"<e>");
        if(($page>$count)&($count>0)){
            $new_index=$page % $count;
            $new_data=$array[$new_index];
        }else{
            $new_data=$product.' '.$array[$page-1];
       
            
        }
        
        return $new_data;
    }
    
    public function setProductMetadata($product,$model){
        $page=Mage::getSingleton('core/session')->getPage($page);
        $custom_keywords=$model->getKeywords();
        $custom_description=$model->getDescription();
        $custom_title=$model->getTitle();
        $keywords=$product->getMetaKeyword();
        $description=$product->getMetaDescription();
        $title=$product->getMetaTitle();
        $newKeywords=$this->generateNewMetadata($keywords,$custom_keywords);
        $newDescription=$this->generateNewMetadata($description,$custom_description);
        $newTitle=$this->generateNewMetadata($title,$custom_title);
        Mage::Log($newTitle,null,'new.log');
        $product->setMetaKeyword($newKeywords);
        $product->setMetaDescription($newDescription);
        $product->setMetaTitle($newTitle);
        Mage::Log("sdfdsfsd",null,'observer.log');
        
    }
    
     public function setCategoryMetadata($category,$model){
        $custom_keywords=$model->getKeywords();
        $custom_description=$model->getDescription();
        $custom_title=$model->getTitle();
        $keywords=$category->getMetaKeywords();
        $description=$category->getMetaDescription();
        $title=$category->getMetaTitle();
        $newKeywords=$this->generateNewMetadata($keywords,$custom_keywords);
        $newDescription=$this->generateNewMetadata($description,$custom_description);
        $newTitle=$this->generateNewMetadata($title,$custom_title);
        $category->setMetaKeywords($newKeywords);
        $category->setMetaDescription($newDescription);
        $category->setMetaTitle($newTitle);
        
    }
    
   public function add_custom_metadata($observer)
   {
        $event=$observer->getEvent();
        $product=$event->getProduct();
         Mage::Log($product->getCategoryIds(),null,'cat.log');
        $customModel=Mage::getModel('metadata/metadata');
        $customCollection=$customModel->getCollection();
        $page=Mage::getSingleton('core/session')->getPage($page);
        foreach ($customCollection as $custom)
   {        
             $this->generateForProductsOfCat($product,$custom);
             $this->generateForProductsOfCatandChild($product,$custom);
              $this->applyToProductsOfField($product,$custom);
             
   }
 
  
  }
   public function add_custom_category_metadata($observer){
       //
       
       //для категорий
     $page=Mage::getBlockSingleton('page/html_pager')->getCurrentPage();
     Mage::getSingleton('core/session')->setPage($page);
     $event=$observer->getEvent();
     $category=$event->getCategory();
     if(empty($page)){
         $page=1;
     }
     $custom_model=Mage::getModel('metadata/metadata');//get custom model
     $custom_collection=$custom_model->getCollection();     
        foreach($custom_collection as $item){
         $this->applyToCategory($category,$item);
         $this->applyToCategoryAndChild($category,$item);
         $this->applyToCategoriesOfField($category,$item);
    }
       
            
        }
     
   
}
