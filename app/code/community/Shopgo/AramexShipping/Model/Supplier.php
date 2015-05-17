<?php

class Shopgo_AramexShipping_Model_Supplier
    extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('aramexshipping/supplier');
    }

    public function isSupplierCodAccountSet($quote)
    {
        $result = false;

        foreach ($quote->getAllVisibleItems() as $item) {
            $supplierId = Mage::getModel('catalog/product')
                ->load($item->getProductId())->getAramexSupplier();

            if (!$supplierId) {
                continue;
            }

            $supplier = Mage::getModel('aramexshipping/supplier')->load($supplierId);

            $result = Mage::helper('aramexshipping')->isCodAccountSet(array(
                'cod_account_number' => $supplier->getCodAccountNumber(),
                'cod_account_number' => $supplier->getCodAccountPin()
            ));

            if ($result) {
                break;
            }
        }

        return $result;
    }
}
