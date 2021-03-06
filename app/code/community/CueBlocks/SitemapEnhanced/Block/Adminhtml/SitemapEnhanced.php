<?php

/**
 * Description of 
 * @package   CueBlocks_SitemapEnhanced
 * @company   CueBlocks - http://www.cueblocks.com/
 * @author    Francesco Magazzu' <francesco.magazzu at cueblocks.com>
 * 
 */
class CueBlocks_SitemapEnhanced_Block_Adminhtml_SitemapEnhanced extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    /**
     * Block constructor
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_sitemapEnhanced';
        $this->_blockGroup = 'sitemapEnhanced';
        $this->_headerText = Mage::helper('sitemapEnhanced')->__('Manage Sitemaps');
        $this->_addButtonLabel = Mage::helper('sitemap')->__('Add Sitemap');

        parent::__construct();
    }

}
