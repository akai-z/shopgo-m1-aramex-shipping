<?php

class Shopgo_AramexShipping_Model_Product_Attribute_Source_Suppliers
    extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        $this->_options = array();
        $suppliers = Mage::helper('aramexshipping')->getSuppliersCollection();

        foreach ($suppliers as $supplier) {
            $this->_options[] = array(
                'value' => $supplier->getId(),
                'label' => $supplier->getIdentifier()
            );
        }

        array_unshift($this->_options, array('value' => '', 'label' => Mage::helper('adminhtml')->__('None')));

        return $this->_options;
    }
}
