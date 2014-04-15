<?php

class Shopgo_AramexShipping_Block_Adminhtml_Pickup
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'aramexshipping';
        $this->_controller = 'adminhtml_pickup';
        $this->_headerText = Mage::helper('aramexshipping')->__('Aramex Shipping Pickups Manager');
        //$this->_addButtonLabel = Mage::helper('aramexshipping')->__('Create Pickup');
        parent::__construct();
        $this->removeButton('add');
    }
}
