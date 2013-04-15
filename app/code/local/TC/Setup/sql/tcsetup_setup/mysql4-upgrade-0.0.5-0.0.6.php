<?php
/**
 * @category TC
 * @package TC_Setup
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'original_url');
$attributeModel->setApplyTo(array())->save();

$installer->endSetup();

