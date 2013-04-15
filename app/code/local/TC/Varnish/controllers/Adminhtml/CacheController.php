<?php
/**
 * @category TC
 * @package TC_Varnish
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'CacheController.php';
class TC_Varnish_Adminhtml_CacheController extends Mage_Adminhtml_CacheController{

	public function varnishPurgeAction(){
		try {
			foreach (Mage::helper('tc_varnish')->getServers() as $server){
				Mage::getModel('tc_varnish/request', $server)->purge($this->getRequest()->getParam('purge_url', '.*'));
			}
			$this->_getSession()->addSuccess(Mage::helper('adminhtml')->__("Varnish cache has been flushed"));
		} catch (Exception $e) {
			$this->_getSession()->addError(Mage::helper('adminhtml')->__("Error: %s", $e->getMessage()));
		}

		$this->_redirect('*/*');
	}
}