<?php

class Shopgo_AramexShipping_Model_System_Config_Source_Producttypes
{
    public function toOptionArray()
    {
        return array(
            array('label' => Mage::helper('aramexshipping')->__('Priority Document Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::PRIORITY_DOCUMENT_EXPRESS),
            array('label' => Mage::helper('aramexshipping')->__('Priority Parcel Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::PRIORITY_PARCEL_EXPRESS),
            array('label' => Mage::helper('aramexshipping')->__('Priority Letter Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::PRIORITY_LETTER_EXPRESS),
            array('label' => Mage::helper('aramexshipping')->__('Deferred Document Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::DEFERRED_DOCUMENT_EXPRESS),
            array('label' => Mage::helper('aramexshipping')->__('Deferred Parcel Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::DEFERRED_PARCEL_EXPRESS),
            array('label' => Mage::helper('aramexshipping')->__('Ground Document Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::GROUND_DOCUMENT_EXPRESS),
            array('label' => Mage::helper('aramexshipping')->__('Ground Parcel Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::GROUND_PARCEL_EXPRESS),
            array('label' => Mage::helper('aramexshipping')->__('Economy Document Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::ECONOMY_DOCUMENT_EXPRESS),
            array('label' => Mage::helper('aramexshipping')->__('Economy Parcel Express'),
                'value' => Shopgo_AramexShipping_Model_Shipment::ECONOMY_PARCEL_EXPRESS)
        );
    }
}
