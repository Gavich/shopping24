<?php

class Itdelight_Metadata_Block_Adminhtml_Metadata_Edit_Tab_Category extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('category_form');
        $this->setTitle(Mage::helper('metadata')->__('Metadata Information'));
       
    }
    public function getCategoriesArray() {

    $categoriesArray = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('path', 'asc')
            ->load()
            ->toArray();

    $categories = array();
    foreach ($categoriesArray as $categoryId => $category) {
        if (isset($category['name']) && isset($category['level'])) {
            $categories[] = array(
                'label' => $category['name'],
                'level'  =>$category['level'],
                'value' => $categoryId
            );
        }
    }

    return $categories;
}
    protected function get_categories(){

    $category = Mage::getModel('catalog/category'); 
    $tree = $category->getTreeModel(); 
    $tree->load();
    $ids = $tree->getCollection()->getAllIds(); 
    $arr = array();
    if ($ids){ 
    foreach ($ids as $id){ 
    $cat = Mage::getModel('catalog/category');
    $cat->load($id);

    $arr[$id] = $cat->getName();
    
   }
    }

    return $arr;

}

    protected function _prepareForm()
    {
 
        
        $form = new Varien_Data_Form();
 
     $fieldset = $form->addFieldset('category_metadata', array('legend'=>Mage::helper('metadata')->__('Category Metadata')));
  
  
        $fieldset->addField('title', 'text', array(
             'label'     => Mage::helper('metadata')->__('Title'),
             'class'     => 'title',
             'required'  => true,
             'name'      => 'title',
//             'note'     => Mage::helper('metadata')->__('The title'),
        ));
 
        $fieldset->addField('description', 'textarea', array(
             'label'     => Mage::helper('metadata')->__('Description'),
             'class'     => 'description',
             'required'  => true,
             'name'      => 'description',
        ));
 $fieldset->addField('cat', 'checkbox', array(
          'label'     => Mage::helper('metadata')->__('Apply to checked category'),
          'name'      => 'cat',
          'checked' => false,
          'onclick' => "",
          'onchange' => "",
          'value'  => '1',
          'disabled' => false,
          'tabindex' => 1
        ));
  
  $fieldset->addField('cat_child', 'checkbox', array(
          'label'     => Mage::helper('metadata')->__('Apply to checked category and children'),
          'name'      => 'cat_child',
          'checked' => false,
          'onclick' => "",
          'onchange' => "",
          'value'  => '1',
          'disabled' => false,
          'tabindex' => 1
        ));
       $fieldset->addField('cat_form', 'checkbox', array(
          'label'     => Mage::helper('metadata')->__('Apply to categories in form'),
          'name'      => 'cat_form',
          'checked' => false,
          'onclick' => "",
          'onchange' => "",
          'value'  => '1',
          'disabled' => false,
          'tabindex' => 1
        ));
       $fieldset->addField('prod_cat', 'checkbox', array(
          'label'     => Mage::helper('metadata')->__('Apply to a products in current category'),
          'name'      => 'prod_cat',
          'checked' => false,
          'onclick' => "",
          'onchange' => "",
          'value'  => '1',
          'disabled' => false,
          'tabindex' => 1
        ));
        $fieldset->addField('prod_childcat', 'checkbox', array(
          'label'     => Mage::helper('metadata')->__('Apply to a products in current category and children categories'),
          'name'      => 'prod_childcat',
          'checked' => false,
          'onclick' => "",
          'onchange' => "",
          'value'  => '1',
          'disabled' => false,
          'tabindex' => 1
        ));
         $fieldset->addField('prod_form', 'checkbox', array(
          'label'     => Mage::helper('metadata')->__('Apply to a products in text field'),
          'name'      => 'prod_form',
          'checked' => false,
          'onclick' => "",
          'onchange' => "",
          'value'  => '1',
          'disabled' => false,
          'tabindex' => 1
        ));
         
        $fieldset->addField('keywords', 'textarea', array(
             'label'     => Mage::helper('metadata')->__('Keywords'),
             'class'     => 'keywords',
             'required'  => true,
             'name'      => 'keywords',
        ));
//       
//     $fieldset->addField('cat_select', 'select', array(
//      'label'     => 'Category',
//      'class'     => 'keywords',
//      'required'  => false,
//      'name'      => 'category_id',
//      'values' => $this->get_categories(),
//      'disabled' => false,
//      'readonly' => false,
//      'tabindex' => 1
//    ));
//     
     $fieldset->addField('products', 'text', array(
             'label'     => Mage::helper('metadata')->__('Products'),
             'class'     => 'text',
             'required'  => false,
             'name'      => 'products',
             'note'     => Mage::helper('metadata')->__('Enter products id here'),
        ));
        
     $fieldset->addField('categories', 'text', array(
             'label'     => Mage::helper('metadata')->__('Categories'),
             'class'     => 'text',
             'required'  => false,
             'name'      => 'categories',
             'note'     => Mage::helper('metadata')->__('Enter categories id here'),
        ));
     
      
        
        $this->setForm($form);
 
        return parent::_prepareForm();
    }



}
