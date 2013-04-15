<?php
/**
 * @category TC
 * @package TC_Varnish
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Varnish_Model_Source_Varnishversion{

	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray(){
		return array(
			array('value' => 2, 'label' => Mage::helper('adminhtml')->__('Version 2.x')),
			array('value' => 3, 'label' => Mage::helper('adminhtml')->__('Version 3.x')),
		);
	}
}
