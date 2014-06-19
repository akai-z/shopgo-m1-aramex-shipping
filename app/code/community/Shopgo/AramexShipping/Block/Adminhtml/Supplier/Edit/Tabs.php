<?php

class Shopgo_AramexShipping_Block_Adminhtml_Supplier_Edit_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('aramex_supplier_form_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('aramexshipping')->__('Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('general_info_section', array(
            'label'   => Mage::helper('aramexshipping')->__('General Information'),
            'title'   => Mage::helper('aramexshipping')->__('General Information'),
            'content' => $this->getLayout()->createBlock('aramexshipping/adminhtml_supplier_edit_tab_generalinfoform')->toHtml()
        ));

        $this->addTab('aramex_account_section', array(
            'label'   => Mage::helper('aramexshipping')->__('Aramex Account'),
            'title'   => Mage::helper('aramexshipping')->__('Aramex Account'),
            'content' => $this->getLayout()->createBlock('aramexshipping/adminhtml_supplier_edit_tab_aramexaccountform')->toHtml()
        ));

        $this->addTab('aramex_settings_section', array(
            'label'   => Mage::helper('aramexshipping')->__('Aramex Settings'),
            'title'   => Mage::helper('aramexshipping')->__('Aramex Settings'),
            'content' => $this->getLayout()->createBlock('aramexshipping/adminhtml_supplier_edit_tab_aramexsettingsform')->toHtml()
        ));

        return parent::_beforeToHtml();
    }
}
