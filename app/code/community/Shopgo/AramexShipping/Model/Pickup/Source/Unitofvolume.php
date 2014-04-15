<?php

class Shopgo_AramexShipping_Model_Pickup_Source_Unitofvolume
{
    public function toOptionArray()
    {
        $unitArr = Mage::getSingleton('aramexshipping/shipment')->getCode('unit_of_volume');

        $returnArr = array();
        foreach ($unitArr as $key => $val) {
            $returnArr[] = array('value' => $key, 'label' => $val);
        }

        return $returnArr;
    }
}
