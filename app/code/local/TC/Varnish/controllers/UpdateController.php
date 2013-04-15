<?php
	/**
	 * @category TC
	 * @package TC_Varnish
	 * @author Aleksandr Smaga <smagaan@gmail.com>
	 */

class TC_Varnish_UpdateController extends Mage_Core_Controller_Front_Action{

	public function preDispatch(){
		if ($referer = $this->getRequest()->getPost('referer_uenc', false)){
			$this->getRequest()->setParam('referer', $referer);
		}

		parent::preDispatch();
	}

	/**
	 * Returns user specific variables via AJAX
	 */
	public function indexAction(){
		if (!$this->getRequest()->isXmlHttpRequest() || !$this->getRequest()->isPost()){
			echo 'Only POST XmlHttp request method allowed!';
			return;
		}

		$blocks = $this->getRequest()->getPost('blocks', array());
		$result = array();

		$messages = $this->getRequest()->getPost('messages', array());
		if (!empty($messages)){
			$messagesToInit = array();
			foreach($messages as $storage){
				$messagesToInit[] = $storage . '/session';
			}
			$this->initLayoutMessages($messagesToInit);
		}

		$helper = Mage::helper('tc_varnish/html');
		foreach ($blocks as $id => $block){
			$result['blocks'][$id] = $helper->{$block['name']}();
		}

		$this->getResponse()->setBody(Zend_Json::encode($result));
	}

	/**
	 * Returns top cart details html via AJAX
	 */
	public function cartAction(){
		if (!$this->getRequest()->isXmlHttpRequest() || !$this->getRequest()->isPost()){
			echo 'Only POST XmlHttp request method allowed!';
			return;
		}

		$helper = Mage::helper('tc_varnish/html');

		$result =$helper->top_cart();
		$this->getResponse()->setBody($result);
	}
}
