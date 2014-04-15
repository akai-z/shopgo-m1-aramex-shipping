<?php

class Shopgo_AramexShipping_Model_Supplier
    extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('aramexshipping/supplier');
    }
}
