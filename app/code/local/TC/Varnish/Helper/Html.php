<?php
	/**
	 * @category TC
	 * @package TC_Varnish
	 * @author Aleksandr Smaga <smagaan@gmail.com>
	 */

class TC_Varnish_Helper_Html extends Mage_Core_Helper_Abstract{

	public function __call($method, $params){
		$method = 'get' . uc_words($method, '');

		if (method_exists($this, $method)){
			return $this->{$method}();
		}else{
			return 'error';
		}
	}

	/**
	 * Return customer login or logout urls data
	 * @return array
	 */
	public function getLoginLink(){
		/** @var $helper Mage_Customer_Helper_Data */
		$helper = Mage::helper('customer');

		$result = array();
		if ($helper->isLoggedIn()){
			$result['url'] = $helper->getLogoutUrl();
			$result['text'] = $helper->__('Log out');
		}else{
			$result['url'] = $helper->getLoginUrl();
			$result['text'] = $helper->__('Login or Register');
		}

		return $result;
	}

	/**
	 * Returns cart header data
	 * @return array
	 */
	public function getTopCartHeader(){
		$_subtotals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();
		$subtotal = $_subtotals["subtotal"]->getValue();
		$subtotalString ='&nbsp;' . Mage::helper('checkout')->formatPrice($subtotal);

		$qty = Mage::getSingleton('checkout/cart')->getSummaryQty()?Mage::getSingleton('checkout/cart')->getSummaryQty():'0';

		$href = $qty > 0 ? 'href="' . Mage::getUrl('checkout/cart') . '"' : '';

		return array(
			'subtotal' => $subtotalString,
			'href' => $href,
			'qty' => $qty
		);
	}

	/**
	 * Returns shopping cart details html
	 * @return string
	 */
	public function getTopCart(){
		$block = Mage::app()->getLayout()->createBlock('checkout/cart_sidebar', 'cart_sidebar');
		$block->setTemplate('checkout/cart/sidebar.phtml');

		return $block->toHtml();
	}

	/**
	 * Returns messages html
	 * @return string
	 */
	public function getMessages(){
		return Mage::app()->getLayout()->getMessagesBlock()->getGroupedHtml();
	}

    /**
     * Returns cms menu block
     * @return string
     */
    public function getCmsMainmenu(){
        $block = Mage::app()->getLayout()->createBlock('customwidget/menu', 'cms_mainmenu');
        $block->setBlockId('mainmenu');

        return $block->toHtml();
    }
}
