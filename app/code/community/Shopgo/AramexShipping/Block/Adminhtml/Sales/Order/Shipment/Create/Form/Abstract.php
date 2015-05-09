<?php

abstract class Shopgo_AramexShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Form_Abstract
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
            $helper = Mage::helper('aramexshipping');

            switch (true) {
                case $data = $helper->getConfigData($field, 'shipping_origin'):
                    break;
                case $data = $helper->getConfigData($field, 'additional_info'):
                    break;
                case $data = $helper->getConfigData($field, 'aramex_settings'):
                    break;
            }
        }

        return $data;
    }
}
