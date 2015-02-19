<?php

class Shopgo_AramexShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Form_Shipment
    extends Shopgo_AramexShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Form_Abstract
{
    protected function _construct()
    {
        parent::_construct();

        $shipmentData = Mage::getSingleton('adminhtml/session')->getShipAramexShipmentData();

        if ($shipmentData) {
            $this->setFormData($shipmentData);
            Mage::getSingleton('adminhtml/session')->unsShipAramexShipmentData();
            if (Mage::registry('setShipAramexFormDefaultData')) {
                Mage::unregister('setShipAramexFormDefaultData');
            }
        } else {
            if (!Mage::registry('setShipAramexFormDefaultData')) {
                Mage::register('setShipAramexFormDefaultData', 1);
            }
        }
    }

    public function isEnabled()
    {
        return Mage::getModel('aramexshipping/shipment')->isEnabled();
    }
}
