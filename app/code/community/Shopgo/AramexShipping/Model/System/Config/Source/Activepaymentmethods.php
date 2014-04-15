<?php

class Shopgo_AramexShipping_Model_System_Config_Source_Activepaymentmethods
{
    public function toOptionArray($isMultiSelect = false)
    {
        $options = Mage::helper('payment')->getPaymentMethodList(true, true, true);

        if ($isMultiSelect) {
            array_unshift($options, array('value' => '', 'label' => Mage::helper('adminhtml')->__('--Please Select--')));
        }

        return $options;
    }
}
