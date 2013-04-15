<?php

/**
 * Description of 
 * @package   CueBlocks_SitemapEnhanced
 * @company   CueBlocks - http://www.cueblocks.com/
 * @author    Francesco Magazzu' <francesco.magazzu at cueblocks.com>
 */
class CueBlocks_SitemapEnhanced_Block_Adminhtml_SitemapEnhanced_Grid_Renderer_Link extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Prepare link to display in grid
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        /* @var $row CueBlocks_SitemapEnhanced_Model_SitemapEnhanced */
        
        $html     = '';
        $helper   = $row->getHelper();
        $pathmap  = $helper->getGeneralConf($row->storeId,true)->getPathMap();
        $fileUrl  = $row->getLinkForRobots();
        $fileName = $fileUrl['filename'];
        $url      = $fileUrl['url'];

//        if ($pathmap)
//            $filePath = $helper->fixRelative(BP . DS . $pathmap);

        if (file_exists(BP . DS . $pathmap . $fileName)) {
            $html .= sprintf('<div><a target="_blank" href="%1$s">%1$s</a></div>', $url);
        } else {
            $html .= sprintf('<div>%1$s</div>', $url);
        }

        return $html;
    }

}
