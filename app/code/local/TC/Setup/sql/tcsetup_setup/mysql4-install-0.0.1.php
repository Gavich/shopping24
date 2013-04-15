<?php
/**
 * @category TC
 * @package TC_Setup
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

/* @var $installer Mage_Eav_Model_Entity_Setup */
$installer = $this;

$installer->startSetup();

$this->addAttribute('catalog_category', 'original_id', array(
	'type' => 'varchar',
	'label'=> 'otto.de orginal ID',
	'input' => 'text',
	'required' => false,
	'sort_order'  => 1,
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	'group' => 'Special Attributes',
));

$this->addAttribute('catalog_category', 'short_description', array(
	'type' => 'text',
	'label' => 'Short description',
	'input' => 'textarea',
	'required' => false,
	'sort_order'  => 4,
	'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'wysiwyg_enabled' => true,
	'is_html_allowed_on_front' => true,
	'group' => 'General Information',
));

$installer->endSetup();
