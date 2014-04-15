<?php

class Shopgo_AramexShipping_Model_System_Config_Source_Shippingmode
{
    public function toOptionArray()
    {
        return array(
            array('label' => Mage::helper('aramexshipping')->__('Auto'),
                'value' => 'auto'),
            array('label' => Mage::helper('aramexshipping')->__('Manual'),
                'value' => 'manual')
        );
    }
}
