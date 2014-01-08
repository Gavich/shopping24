<?php

class TC_Catalog_Block_Robots extends Mage_Core_Block_Template
{
    /**
     * Set robots to NOINDEX, NOFOLLOW on pages where this block will be added through layout xml configuration
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setRobots('NOINDEX, FOLLOW');
        }

        return parent::_prepareLayout();
    }
}
