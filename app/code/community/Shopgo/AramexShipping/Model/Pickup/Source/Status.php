<?php

class Shopgo_AramexShipping_Model_Pickup_Source_Status
{
    public function toOptionArray()
    {
        return array(
            array('label' => Mage::helper('aramexshipping')->__('Pending'),
                'value' => 'Pending'),
            array('label' => Mage::helper('aramexshipping')->__('Ready'),
                'value' => 'Ready')
        );
    }
}
