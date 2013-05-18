<?php

class Itdelight_Metadata_Model_Observer {
    
    public function _construct()
    {
        
    }
   public function add_custom_metadata($observer)
   {
        $event=$observer->getEvent();
        $product=$event->getProduct();

        $categories=$product->getCategoryIds();
        foreach ($categories as $category)
{
    $info = Mage::getModel('catalog/category')
            ->load($category);
    if($info->getLevel()>2){
        $info->getParentCategories();
    }
}
   $customModel=Mage::getModel('metadata/metadata');
   $customCollection=$customModel->getCollection();
   $page=Mage::getBlockSingleton('page/html_pager')->getCurrentPage();
   foreach( $customCollection as $custom)
   {
       if($custom->getCategoryId()==$info->getId())
           {
             
             $title=$product->getMetaTitle();
             $meta=$product->getMetaKeyword();
              $myArray=explode("<e>", $meta);
                   $custom_new=$myArray[$page+1];
             $description=$product->getMetaDescription();
             $metaNew=$custom_new.' '.$custom->getKeywords();
             $descriptionNew=$description.' '.$custom->getDescription();
             $titleNew=$title.' '.$custom->getTitle();
             $product->setMetaDescription($descriptionNew);
             $product->setMetaKeyword($metaNew);
             $product->setMetaTitle($titleNew);

           }
   }
  }
   public function add_custom_category_metadata($observer){
     
     $event=$observer->getEvent();
     $category=$event->getCategory();
     $customModel=Mage::getModel('metadata/metadata');
     $customCollection=$customModel->getCollection();
     $page=Mage::getBlockSingleton('page/html_pager')->getCurrentPage();
     foreach($customCollection as $custom_item){
            if($custom_item->getCategoryId()==$category->getId())
                {
                   $keywords=$category->getMetaKeywords();
                   $keyword_custom=$custom_item->getKeywords();
                   $myArray=explode("<e>", $keyword_custom);
                   $custom=$myArray[$page+1];  
                   //Mage::Log($custom,null,'custom.log');
                   $new_keywords=$keywords.' '.$custom;
                   $category->setMetaKeywords($new_keywords);
                   $title=$category->getMetaTitle();
                   $title_custom=$custom_item->getTitle();
                   $new_title=$title.' '.$title_custom;
                   $category->setMetaTitle($new_title);
                   $description_custom=$custom_item->getDescription();
                   $description=$category->getMetaDescription();
                   $new_description=$description.' '.$description_custom;
                   $category->setMetaDescription($new_description);
                }
      }    
   }
}
