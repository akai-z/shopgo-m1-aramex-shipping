<?php

class Shopgo_AramexShipping_Model_System_Config_Source_Unitofmeasure
{
    public function toOptionArray()
    {
        $unitArr = Mage::getSingleton('aramexshipping/shipment')->getCode('unit_of_measure');

        $returnArr = array();
        foreach ($unitArr as $key => $val) {
            $returnArr[] = array('value' => $key, 'label' => $val);
        }

        return $returnArr;
    }
}
