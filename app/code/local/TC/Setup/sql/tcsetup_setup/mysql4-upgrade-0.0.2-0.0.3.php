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
	'type' => 'int',
	'label'=> '',
	'input' => 'select',
	'required' => false,
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	'group' => 'Parameters',
	'user_defined' => 1
);

$entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
$object = new Varien_Object();
$object->setData($data);

//adding configurable
$configurableAttr = array(
	'Dim 1' => 'dim1',
	'Dim 2' => 'dim2',
	'Dim 3' => 'dim3',
	'Dim 4' => 'dim4',
	'Dim 5' => 'dim5'
);
foreach ($configurableAttr as $label => $code) {
	$object
		->setLabel($label);

	$this->addAttribute($entityTypeId, $code, $object->getData());
	$attributeModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', $code);
	$attributeModel
		->setApplyTo('simple')
		->setIsConfigurable(true)
		->setIsSearchable(true)
		->setIsVisibleInAdvancedSearch(true)
		->setIsFilterable(true)
		->save();
	unset($attributeModel);
}
unset($configurableAttr);

$installer->endSetup();
