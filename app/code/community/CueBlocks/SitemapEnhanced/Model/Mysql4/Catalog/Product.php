<?php

/**
 * Description of SitemapEnhanced
 * @package   CueBlocks_SitemapEnhanced
 * @company    CueBlocks - http://www.cueblocks.com/
 * @author    Francesco Magazzu' <francesco.magazzu at cueblocks.com>
 */
class CueBlocks_SitemapEnhanced_Model_Mysql4_Catalog_Product extends Mage_Sitemap_Model_Mysql4_Catalog_Product
{

    /**
     * Get category collection array
     *
     * @param unknown_type $storeId
     * @return array
     */
    public function getCollection($storeId, $filterOutOfStock = false, $filterInStock = false, $catId = null)
    {

        $store = Mage::app()->getStore($storeId);
        /* @var $store Mage_Core_Model_Store */

        if (!$store) {
            return false;
        }

        $urConditions = array(
            'e.entity_id=ur.product_id',
            'ur.category_id IS NULL',
            $this->_getWriteAdapter()->quoteInto('ur.store_id=?', $store->getId()),
            $this->_getWriteAdapter()->quoteInto('ur.is_system=?', 1),
        );

        // filter for category
        if ($catId) {
            $catConditions = array(
                'e.entity_id=cat_index.product_id',
                $this->_getWriteAdapter()->quoteInto('cat_index.store_id=?', $store->getId()),
                $this->_getWriteAdapter()->quoteInto('cat_index.category_id=?', $catId),
                $this->_getWriteAdapter()->quoteInto('cat_index.is_parent=?', 1),
            );

            $urConditions = array(
                'e.entity_id=ur.product_id',
                $this->_getWriteAdapter()->quoteInto('ur.category_id=?', $catId),
                $this->_getWriteAdapter()->quoteInto('ur.store_id=?', $store->getId()),
                $this->_getWriteAdapter()->quoteInto('ur.is_system=?', 1),
            );
        } else {

            $rootId = $store->getRootCategoryId();

            $_category        = Mage::getModel('catalog/category')->load($rootId); //get category model
            $child_categories = $_category->getResource()->getChildren($_category, false); //array consisting of all child categories id
            $child_categories = array_merge(array($rootId), $child_categories);

// filter product that doesn't belongs to the store root category childs
            $catConditions = array(
                'e.entity_id=cat_index.product_id',
                $this->_getWriteAdapter()->quoteInto('cat_index.store_id=?', $store->getId()),
//                $this->_getWriteAdapter()->quoteInto('cat_index.category_id=?', $rootId),
                $this->_getWriteAdapter()->quoteInto('cat_index.category_id in (?)', $child_categories),
                $this->_getWriteAdapter()->quoteInto('cat_index.position!=?', 0),
            );
        }

        $this->_select = $this->_getWriteAdapter()->select()
                ->from(array('e' => $this->getMainTable()), array($this->getIdFieldName()))
                ->join(
                        array('w' => $this->getTable('catalog/product_website')), 'e.entity_id=w.product_id', array()
                )
                ->where('w.website_id=?', $store->getWebsiteId())
                ->joinLeft(
                        array('ur' => $this->getTable('core/url_rewrite')), join(' AND ', $urConditions), array('url' => 'request_path'))
                ->join(
                        array('cat_index' => $this->getTable('catalog/category_product_index')), join(' AND ', $catConditions), array()
                )
                // distinct products
                ->distinct(true);


// filter in/out of stock
        if ($filterOutOfStock) {
            $stkConditions = array(
                'e.entity_id=stk.product_id',
                $this->_getWriteAdapter()->quoteInto('stk.is_in_stock=?', 1)
            );
            $this->_select = $this->_select->join(
                    array('stk' => $this->getTable('cataloginventory/stock_item')), join(' AND ', $stkConditions), array('is_in_stock' => 'is_in_stock'));
        } elseif ($filterInStock) {
            $stkConditions = array(
                'e.entity_id=stk.product_id',
                $this->_getWriteAdapter()->quoteInto('stk.is_in_stock=?', 0)
            );
            $this->_select = $this->_select->join(
                    array('stk' => $this->getTable('cataloginventory/stock_item')), join(' AND ', $stkConditions), array('is_in_stock' => 'is_in_stock'));
        }

        $this->_addFilter($storeId, 'visibility', Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds(), 'in');
        $this->_addFilter($storeId, 'status', Mage::getSingleton('catalog/product_status')->getVisibleStatusIds(), 'in');

//       die((string) ($this->_select));

        $query = $this->_getWriteAdapter()->query($this->_select);

        return $query;
    }

}
