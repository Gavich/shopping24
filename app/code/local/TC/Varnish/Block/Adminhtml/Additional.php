<?php
/**
 * @category TC
 * @package TC_Varnish
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Varnish_Block_Adminhtml_Additional extends Mage_Adminhtml_Block_Template{

	/**
	 * Get controller url
	 */
	public function getPurgeUrl(){
		return $this->getUrl('*/*/varnishPurge');
	}
}