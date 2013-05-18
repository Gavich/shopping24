<?php

class Itdelight_Metadata_Block_Adminhtml_Metadata_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('block_form');
        $this->setTitle(Mage::helper('metadata')->__('Metadata Information'));
       
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
 if($cat->getLevel()==2)
    $arr[$id] = $cat->getName();
    
   }
    }

    return $arr;

}

    protected function _prepareForm()
    {
 
        if (Mage::getSingleton('adminhtml/session')->getExampleData())
        {
            $data = Mage::getSingleton('adminhtml/session')->getExamplelData();
            Mage::getSingleton('adminhtml/session')->getExampleData(null);
        }
        elseif (Mage::registry('current_metadata'))
        {
            $data = Mage::registry('current_metadata')->getData();
        }
        else
        {
            $data = array();
        }
 
        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data',
        ));
 
        $form->setUseContainer(true);
 
        $this->setForm($form);
 
        $fieldset = $form->addFieldset('example_form', array(
             'legend' =>Mage::helper('metadata')->__('Metadata Information')
        ));
 
        $fieldset->addField('title', 'text', array(
             'label'     => Mage::helper('metadata')->__('Title'),
             'class'     => 'required-entry',
             'required'  => true,
             'name'      => 'title',
             'note'     => Mage::helper('metadata')->__('The title'),
        ));
 
        $fieldset->addField('description', 'textarea', array(
             'label'     => Mage::helper('metadata')->__('Description'),
             'class'     => 'required-entry',
             'required'  => true,
             'name'      => 'description',
        ));
 
        $fieldset->addField('keywords', 'textarea', array(
             'label'     => Mage::helper('metadata')->__('Keywords'),
             'class'     => 'required-entry',
             'required'  => true,
             'name'      => 'keywords',
        ));
       
     $fieldset->addField('cat_select', 'select', array(
      'label'     => 'Category',
      'class'     => 'required-entry',
      'required'  => true,
      'name'      => 'category_id',
      'values' => $this->get_categories(),
      'disabled' => false,
      'readonly' => false,
      'tabindex' => 1
    ));
        
        
        $form->setValues($data);
 
        return parent::_prepareForm();
    }



}
