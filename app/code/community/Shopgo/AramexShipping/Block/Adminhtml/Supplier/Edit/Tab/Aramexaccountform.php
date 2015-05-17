<?php

class Shopgo_AramexShipping_Block_Adminhtml_Supplier_Edit_Tab_Aramexaccountform
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('aramex_account_form', array('legend' => Mage::helper('aramexshipping')->__('Aramex Account')));

        $fieldset->addField('username', 'text', array(
            'label'    => Mage::helper('aramexshipping')->__('Username'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'username'
        ));

        $fieldset->addField('password', 'obscure', array(
            'label'    => Mage::helper('aramexshipping')->__('Password'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'password'
        ));

        $fieldset->addField('account_country_code', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Account Country Code'),
            'name'  => 'account_country_code'
        ));

        $fieldset->addField('account_entity', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Account Entity'),
            'name'  => 'account_entity'
        ));

        $fieldset->addField('account_number', 'text', array(
            'label' => Mage::helper('aramexshipping')->__('Account Number'),
            'name'  => 'account_number'
        ));

        $fieldset->addField('account_pin', 'obscure', array(
            'label' => Mage::helper('aramexshipping')->__('Account PIN'),
            'name'  => 'account_pin'
        ));

        $fieldset->addField('check_account', 'text', array(
            'name' => 'check_account'
        ))->setRenderer($this->getLayout()->createBlock('aramexshipping/adminhtml_system_config_form_checkAccountButton'));

        $fieldset->addField('cod_account_number', 'obscure', array(
            'label' => Mage::helper('aramexshipping')->__('COD Account Number'),
            'name'  => 'cod_account_number'
        ));

        $fieldset->addField('cod_account_pin', 'obscure', array(
            'label' => Mage::helper('aramexshipping')->__('COD Account PIN'),
            'name'  => 'cod_account_pin'
        ));

        $data = array();

        if (Mage::registry('aramex_suppliers_data')) {
            $data = Mage::registry('aramex_suppliers_data')->getData();
        }

        $form->setValues($data);

        return parent::_prepareForm();
    }
}
