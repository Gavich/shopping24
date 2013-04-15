<?php
/**
 * @category TC
 * @package TC_Setup
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();


$data = array(
	'type' => 'varchar',
	'label'=> '',
	'input' => 'text',
	'required' => false,
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	'group' => 'For import',
	'user_defined' => 1
);

$entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
$object = new Varien_Object();
$object->setData($data);

//adding original_url attribute
$current = clone $object;
$current
	->setLabel('Original url');

$this->addAttribute($entityTypeId, 'original_url', $current->getData());
$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'original_url');
$attributeModel->setApplyTo('configurable')->save();
unset($attributeModel);unset($current);