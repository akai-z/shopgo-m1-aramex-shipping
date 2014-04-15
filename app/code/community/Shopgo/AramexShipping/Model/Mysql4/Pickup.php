<?php

class Shopgo_AramexShipping_Model_Mysql4_Pickup
    extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('aramexshipping/pickup', 'asp_id');
    }
}
