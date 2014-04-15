<?php

class Shopgo_AramexShipping_Model_Mysql4_Supplier
    extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('aramexshipping/supplier', 'asv_id');
    }
}
