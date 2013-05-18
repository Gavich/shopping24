<?php

class Itdelight_Metadata_Block_Adminhtml_Metadata_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
     public function __construct()
    {
        parent::__construct();
        $this->setId('tabs');
        $this->setDestElementId('block_form');
        $this->setTitle(Mage::helper('metadata')->__('Metadata Information'));
    }
     protected function _prepareLayout()
    {

    $this->addTab('tags',array('label'
        => Mage::helper('metadata')->__('First page'),'url'   
        => $this->getUrl('*/*/new', array('_current' => true)),
        ));
        

 
            /**
             * Do not change this tab id
             * @see Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs_Configurable
             * @see Mage_Bundle_Block_Adminhtml_Catalog_Product_Edit_Tabs
             */
            
        return parent::_prepareLayout();
    }
}

