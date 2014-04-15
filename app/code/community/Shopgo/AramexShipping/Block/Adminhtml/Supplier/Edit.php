<?php

class Shopgo_AramexShipping_Block_Adminhtml_Supplier_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'aramexshipping';
        $this->_controller = 'adminhtml_supplier';

        $this->_updateButton('save', 'label', Mage::helper('aramexshipping')->__('Save Supplier'));
        $this->_updateButton('delete', 'label', Mage::helper('aramexshipping')->__('Delete Supplier'));

        $this->_addButton('saveandcontinue', array(
            'label'   => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class'   => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if (Mage::registry('aramex_suppliers_data') && Mage::registry('aramex_suppliers_data')->getId()) {
            return Mage::helper('aramexshipping')->__("Edit Supplier '%s'", $this->htmlEscape(Mage::registry('aramex_suppliers_data')->getIdentifier()));
        } else {
            return Mage::helper('aramexshipping')->__('Add Supplier');
        }
    }
}
