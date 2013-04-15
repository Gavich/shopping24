<?php
/**
 * @category TC
 * @package TC_Setup
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

//make weight not required
$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','weight');
$attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
$attribute->setIsRequired(0)->save();

$data = array(
	'type' => 'varchar',
	'label'=> '',
	'input' => 'text',
	'required' => false,
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	'group' => 'General',
	'user_defined' => 1
);

$entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
$object = new Varien_Object();
$object->setData($data);

//adding original_id attribute
$current = clone $object;
$current
	->setInput('text')
	->setLabel('Original id');

$this->addAttribute($entityTypeId, 'original_id', $current->getData());
$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'original_id');
$attributeModel->setApplyTo(array())->save();
unset($attributeModel);unset($current);

//adding brand_logo attribute
$current = clone $object;
$current
	->setInput('text')
	->setLabel('Brand logo')
	->setGroup('For import');

$this->addAttribute($entityTypeId, 'brand_logo', $current->getData());
$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'brand_logo');
$attributeModel->setApplyTo(array())->save();
unset($attributeModel);unset($current);

//adding init_image attribute
$current = clone $object;
$current
	->setInput('text')
	->setLabel('Init image')
	->setGroup('For import');

$this->addAttribute($entityTypeId, 'init_image', $current->getData());
$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'init_image');
$attributeModel->setApplyTo(array())->save();
unset($attributeModel);unset($current);

//adding main_image attribute
$current = clone $object;
$current
	->setInput('text')
	->setLabel('Main image')
	->setGroup('For import');

$this->addAttribute($entityTypeId, 'main_image', $current->getData());
$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'main_image');
$attributeModel->setApplyTo(array())->save();
unset($attributeModel);unset($current);

//adding some text attributes
$textAttrs = array(
	'Short Description' => 'short_description',
	'Description' => 'description',
	'Description add' => 'descr_add',
	'Description quality' => 'descr_quality',
	'Description form' => 'descr_form',
	'Description brandtext' => 'descr_brandtext',
	'Description instr' => 'descr_instr'
);
foreach ($textAttrs as $label => $code) {
	$current = clone $object;
	$current
		->setInput('textarea')
		->setLabel($label)
		->setGroup('Text attributes')
		->setGlobal(Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE)
		->setType('text');

	$this->addAttribute($entityTypeId, $code, $current->getData());
	$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', $code);
	$attributeModel->setApplyTo(array())->setIsWysiwygEnabled(true)->save();
	unset($attributeModel);unset($current);
}
unset($textAttrs);

//adding market_text attribute
$current = clone $object;
$current
	->setInput('textarea')
	->setLabel('Market text')
	->setGroup('Text attributes')
	->setGlobal(Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE)
	->setType('text');

$this->addAttribute($entityTypeId, 'market_text', $current->getData());
$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'market_text');
$attributeModel->setApplyTo(array('simple'))->setIsWysiwygEnabled(true)->save();
unset($attributeModel);unset($current);

//adding is_photo_processed attribute
$current = clone $object;
$current
	->setInput('select')
	->setLabel('Is photo processed?')
	->setGroup('For import');

//Create options array
$values = array(
	0 => 'Not needed',
	1 => 'Needed',
	2 => 'Done',
);

$this->addAttribute($entityTypeId, 'is_photo_processed', $current->getData());
$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', 'is_photo_processed');
$attr_id = $attributeModel->getId();

$option['attribute_id'] = $attr_id;
$option['values'] = $values;
$this->addAttributeOption($option);
$attributeModel->setApplyTo(array())->setIsConfigurable(false)->save();
unset($attributeModel);unset($current);unset($option);unset($values);

$installer->endSetup();
