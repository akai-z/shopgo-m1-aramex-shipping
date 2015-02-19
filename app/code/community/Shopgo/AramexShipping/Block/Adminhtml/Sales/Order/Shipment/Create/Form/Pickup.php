<?php

class Shopgo_AramexShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Form_Pickup
    extends Shopgo_AramexShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Form_Abstract
{
    protected function _construct()
    {
        parent::_construct();

        $pickupData = Mage::getSingleton('adminhtml/session')->getShipAramexPickupData();

        if ($pickupData) {
            $this->setFormData($pickupData);
            Mage::getSingleton('adminhtml/session')->unsShipAramexPickupData();
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
        return Mage::getModel('aramexshipping/pickup')->isEnabled();
    }

    public function getStatusOptions()
    {
        $options = Mage::getModel('aramexshipping/pickup_source_status')->toOptionArray();
        array_unshift($options, array('value' => '', 'label' => ''));

        return $options;
    }

    public function getUnitOfVolumeOptions()
    {
        $options = Mage::getModel('aramexshipping/pickup_source_unitofvolume')->toOptionArray();
        array_unshift($options, array('value' => '', 'label' => ''));

        return $options;
    }
}
