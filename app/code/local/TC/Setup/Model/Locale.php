<?php
/**
 * @category TC
 * @package TC_Setup
 * @author Aleksandr Nezdoiminoga <alex.n.2k7@gmail.com>
 */

class TC_Setup_Model_Locale
    extends Mage_Core_Model_Locale
{
    public function currency($currency)
    {
        Varien_Profiler::start('locale/currency');
        if (!isset(self::$_currencyCache[$this->getLocaleCode()][$currency])) {
            $options = array();
            try {
                $currencyObject = new Zend_Currency($currency, $this->getLocale());
                //***
                $options = array(
                    'position' => Zend_Currency::RIGHT
                );
                $currencyObject->setFormat($options);
                //***
            } catch (Exception $e) {
                $currencyObject = new Zend_Currency($this->getCurrency(), $this->getLocale());
                $options['name'] = $currency;
                $options['currency'] = $currency;
                $options['symbol'] = $currency;
            }

            $options = new Varien_Object($options);
            Mage::dispatchEvent('currency_display_options_forming', array(
                'currency_options' => $options,
                'base_code' => $currency
            ));

            $currencyObject->setFormat($options->toArray());
            self::$_currencyCache[$this->getLocaleCode()][$currency] = $currencyObject;
        }
        Varien_Profiler::stop('locale/currency');
        return self::$_currencyCache[$this->getLocaleCode()][$currency];
    }
}
