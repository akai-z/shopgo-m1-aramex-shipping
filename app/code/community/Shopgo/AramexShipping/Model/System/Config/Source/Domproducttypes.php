<?php

class Shopgo_AramexShipping_Model_System_Config_Source_Domproducttypes
{
    public function toOptionArray()
    {
        return array(
            array('label' => Mage::helper('aramexshipping')->__('OND'),
                'value' => Shopgo_AramexShipping_Model_Shipment::OND),
            array('label' => Mage::helper('aramexshipping')->__('ONP'),
                'value' => Shopgo_AramexShipping_Model_Shipment::ONP),
            array('label' => Mage::helper('aramexshipping')->__('CDS'),
                'value' => Shopgo_AramexShipping_Model_Shipment::CREDIT_CARDS_DELIVERY)
        );
    }
}
