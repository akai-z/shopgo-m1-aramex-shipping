<?php

class Shopgo_AramexShipping_Block_Adminhtml_Supplier_Edit_Tab_Aramexsettingsform
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('aramex_settings_form', array('legend' => Mage::helper('aramexshipping')->__('Aramex Settings')));

        $fieldset->addType('product_type_select','Shopgo_AramexShipping_Block_Adminhtml_Supplier_Form_Element_ProductType');

        $fieldset->addField('product_type', 'product_type_select', array(
            'label'    => Mage::helper('aramexshipping')->__('Product Type'),
            'name'     => 'product_type',
            'required' => false
        ));

        $fieldset->addType('dom_product_type_select','Shopgo_AramexShipping_Block_Adminhtml_Supplier_Form_Element_DomProductType');

        $fieldset->addField('dom_product_type', 'dom_product_type_select', array(
            'label'    => Mage::helper('aramexshipping')->__('Domestic Product Type'),
            'name'     => 'dom_product_type',
            'required' => false
        ));

        $fieldset->addType('dom_customs_value_select','Shopgo_AramexShipping_Block_Adminhtml_Supplier_Form_Element_DomCustomsValue');

        $fieldset->addField('dom_customs_value', 'dom_customs_value_select', array(
            'label'    => Mage::helper('aramexshipping')->__('Domestic Customs Value'),
            'name'     => 'dom_customs_value',
            'required' => false
        ));

        $data = array();

        if (Mage::registry('aramex_suppliers_data')) {
            $data = Mage::registry('aramex_suppliers_data')->getData();
        }

        $form->setValues($data);

        return parent::_prepareForm();
    }
}
