<?php

abstract class Shopgo_AramexShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Aramex_Abstract
    extends Mage_Adminhtml_Block_Template
{
    abstract protected function isEnabled();

    public function getCountryCodeOptions()
    {
        return Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();
    }

    public function getTimePartsOptions($timePart, $clockSystem = 12)
    {
        return Mage::helper('aramexshipping')->getTimePartsOptions($timePart, $clockSystem);
    }

    public function getFormFieldData($field, $data = '')
    {
        if (Mage::registry('setShipAramexFormDefaultData')) {
            $data = Mage::helper('aramexshipping')
                ->getConfigData($field, 'shipping_origin');
        }

        return $data;
    }
}
