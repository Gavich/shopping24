<?php
/**
 * @category TC
 * @package TC_Varnish
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Varnish_Block_Adminhtml_Cache extends Mage_Adminhtml_Block_Cache{

	/**
	 * Class constructor
	 */
	public function __construct(){
		parent::__construct();
		
		$this->_addButton('purge_varnish', array(
			'label' => Mage::helper('core')->__('Purge varnish'),
			'onclick' => "setLocation('{$this->getPurgeUrl()}')",
			'class' => 'delete',
		));
	}

	public function getPurgeUrl(){
		return $this->getUrl('*/*/varnishPurge');
	}
}
