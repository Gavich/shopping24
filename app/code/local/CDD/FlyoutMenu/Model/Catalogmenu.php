<?php
/**
 * Medusa for Magento 1.7.0.0
 * Design and Development by creative-d2 design&development (http://www.creative-d2.de)
 * Distributed by ThemeForest (http://themeforest.net)
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @author     �mer Bildirici jun.
 * @package    medusa_default
 * @copyright  Copyright 2012 �mer Bildirici jun. (http://www.creative-d2.de)
 * @license    All rights reserved.
 * @version    1.1
 */
class CDD_FlyoutMenu_Model_Catalogmenu extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('FlyoutMenu/catalogmenu');
    }
}