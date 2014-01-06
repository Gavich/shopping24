<?php
/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_SeoSuite
 * @copyright  Copyright (c) 2010 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * SEO Suite extension
 *
 * @category   MageWorx
 * @package    MageWorx_SeoSuite
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */

class MageWorx_SeoSuite_Model_Catalog_Layer_Filter_Item extends Mage_Catalog_Model_Layer_Filter_Item
{
    public function getUrl()
    {
        $var = $this->getFilter()->getRequestVar();
        $attribute = $this->getFilter()->getData('attribute_model'); //->getAttributeCode()
        if ($attribute){
            $value = ($attribute->getAttributeCode() == 'price' || $attribute->getBackendType() == 'decimal') ? $this->getValue() : $this->getLabel();
        } else {
            $value = $this->getLabel();
        }
        $query = array(
            $var => $value,
            Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
        );
        return Mage::helper('seosuite')->getLayerFilterUrl(array('_current'=>true, '_use_rewrite'=>true, '_query'=>$query));
    }

    public function getRemoveUrl()
    {
        $query = array($this->getFilter()->getRequestVar()=>$this->getFilter()->getResetValue());
        $params['_current']     = true;
        $params['_use_rewrite'] = true;
        $params['_query']       = $query;
        $params['_escape']      = true;
        return Mage::helper('seosuite')->getLayerFilterUrl($params);
    }
}
