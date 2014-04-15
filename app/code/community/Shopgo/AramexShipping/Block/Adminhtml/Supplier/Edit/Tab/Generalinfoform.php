<?php

class Shopgo_AramexShipping_Block_Adminhtml_Supplier_Edit_Tab_Generalinfoform
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('general_info_form', array('legend' => Mage::helper('aramexshipping')->__('General information')));

        $fieldset->addField('identifier', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Identifier'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'identifier'
        ));

        $fieldset->addField('address_line1', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Address Line 1'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'address_line1'
        ));

        $fieldset->addField('address_line2', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Address Line 2'),
            'name'  => 'address_line2'
        ));

        $fieldset->addField('address_line3', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Address Line 3'),
            'name'  => 'address_line3'
        ));

        $fieldset->addField('country_code', 'select', array(
            'label'    => Mage::helper('aramexshipping')->__('Country'),
            'class'    => 'required-entry',
            'required' => true,
            'values'   => Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(),
            'name'     => 'country_code'
        ));

        $fieldset->addField('city', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('City'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'city'
        ));

        $fieldset->addField('state_or_province_code', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('State/Province'),
            'name'  => 'state_or_province_code'
        ));

        $fieldset->addField('post_code', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Post Code'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'post_code'
        ));

        $fieldset->addField('department', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Department'),
            'name'  => 'department'
        ));

        $fieldset->addField('person_name', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Supplier Name'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'person_name'
        ));

        $fieldset->addField('title', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Supplier Title'),
            'name'  => 'title'
        ));

        $fieldset->addField('company_name', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Company Name'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'company_name'
        ));

        $fieldset->addField('phone_number1', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Phone Number 1'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'phone_number1'
        ));

        $fieldset->addField('phone_number1_ext', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Phone Number 1 Ext'),
            'name'  => 'phone_number1_ext'
        ));

        $fieldset->addField('phone_number2', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Phone Number 2'),
            'name'  => 'phone_number2'
        ));

        $fieldset->addField('phone_number2_ext', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Phone Number 2 Ext'),
            'name'  => 'phone_number2_ext'
        ));

        $fieldset->addField('fax_number', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Fax Number'),
            'name'  => 'fax_number'
        ));

        $fieldset->addField('cellphone', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Cellphone'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'cellphone'
        ));

        $fieldset->addField('email', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Email'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'email'
        ));

        $fieldset->addField('type', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Type'),
            'name'  => 'type'
        ));

        $data = array();

        if (Mage::registry('aramex_suppliers_data')) {
            $data = Mage::registry('aramex_suppliers_data')->getData();
        }

        $form->setValues($data);

        return parent::_prepareForm();
    }
}
