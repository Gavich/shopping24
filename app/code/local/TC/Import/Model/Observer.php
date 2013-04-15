<?php
	/**
	 * @category TC
	 * @package TC_Import
	 * @author Aleksandr Smaga <smagaan@gmail.com>
	 */

class TC_Import_Model_Observer{

	const ORIGIN_NAME = 'check_availability_and_price';
	const ERROR_AVAIL_CODE = 432;

	/**
	 * Check items availability and price
	 *
	 * @param $observer Varien_Event_Observer
	 * @return TC_Import_Model_Observer
	 * @throws Exception
	 */
	public function checkItem(Varien_Event_Observer $observer){
		$item = $quoteItem = $observer->getEvent()->getItem();
		if ($item->getParentItemId() === null){
			try{
				$url = Mage::getResourceModel('catalog/product')->getAttributeRawValue($item->getProductId(), 'original_url', Mage::app()->getStore()->getId());
				if ($url === false){
					throw new Exception('Can\'t check price and availability for product id: ' . $item->getProductId());
				}
							// урезаем ску 
				$my_sku_internal=$item->getSku();
				$my_sku_internal= substr($my_sku_internal,0,strlen($my_sku_internal)-5);
				$dimensions = Mage::getModel('tcimport/updater')->getDimensionsArray($item->getProductId(), Mage::app()->getStore()->getId());
				if (!isset($dimensions[$my_sku_internal])){
					throw new Exception('SKU not found in dimensions array, id: ' . $item->getProductId());
				}

				//checking availability
				
	
				
				if (!$dimensions[$my_sku_internal]['availability']){
					$this->_addError(
						$item,
						Mage::helper('sales')->__('Sorry, product "%s" is not available now, due to this it was removed from your cart', $item->getName())
					);
					$item->getQuote()->removeItem($item->getId());

					return $this;
				}

				//checking price
				$priceExt = number_format($dimensions[$my_sku_internal]['price'], 2);
				if (number_format($item->getPrice(), 2) * 10 != $priceExt * 10){
					$item->setCustomPrice($priceExt);
					$item->setOriginalCustomPrice($priceExt);
					$item->getProduct()->setIsSuperMode(true);

					/** @var $session Mage_Checkout_Model_Session */
					$session = Mage::getModel('checkout/session');
					$session->addNotice(Mage::helper('sales')->__('Price for product "%s" was changed, please keep this into account.', $item->getName()));
				}

				return $this;
			}catch (Exception $e){
				$this->_addError(
					$item,
					Mage::helper('sales')->__('Sorry, we can\'t retrieve current availability and price for product "%s", due to this it was removed from your cart', $item->getName())
				);
				$item->getQuote()->removeItem($item->getId());

				$message = __FILE__ . ': Error ---------------' . PHP_EOL . $e->getMessage();
				Mage::log($message, 1, 'get_price.log');
			}
		}
	}

	/**
	 * @param $item Mage_Sales_Model_Quote_Item
	 * @param $message string
	 * @return void(0)
	 */
	protected function _addError($item, $message){
		$item->getQuote()->addErrorInfo(
			'error',
			self::ORIGIN_NAME,
			self::ERROR_AVAIL_CODE,
			$message
		);
	}
}