<?php

class Shopgo_AramexShipping_Model_Observer
{
    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        Mage::getModel('aramexshipping/shipment')
            ->prepareShipment($observer->getShipment(), 'neutral');
    }
}
