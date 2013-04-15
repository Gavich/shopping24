<?php
/**
 * @category TC
 * @package TC_Perfomance
 */
/**
 * Rewrite base class to load only needed options instead all
 * to improve cart page load speed
 */
class TC_Perfomance_Model_Product_Type_Configurable extends Mage_Catalog_Model_Product_Type_Configurable
{
    /**
     * Retrieve Selected Attributes info
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getSelectedAttributesInfo($product = null)
    {
        $attributes = array();
        Varien_Profiler::start('CONFIGURABLE:'.__METHOD__);
        if ($attributesOption = $this->getProduct($product)->getCustomOption('attributes')) {
            $data = unserialize($attributesOption->getValue());
            $this->getUsedProductAttributeIds($product);

            $usedAttributes = $this->getProduct($product)->getData($this->_usedAttributes);

            foreach ($data as $attributeId => $attributeValue) {
                if (isset($usedAttributes[$attributeId])) {
                    $attribute = $usedAttributes[$attributeId];
                    $label = $attribute->getLabel();
                    $value = $attribute->getProductAttribute();
                    if ($value->getSourceModel()) {
                        if (!Mage::app()->getStore()->isAdmin()) {
                            $value = $value->getSource()->getNeededOptionText($attributeValue);
                        } else {
                            $value = $value->getSource()->getOptionText($attributeValue);
                        }
                    } else {
                        $value = '';
                    }

                    $attributes[] = array('label'=>$label, 'value'=>$value);
                }
            }
        }
        Varien_Profiler::stop('CONFIGURABLE:'.__METHOD__);
        return $attributes;
    }
}
