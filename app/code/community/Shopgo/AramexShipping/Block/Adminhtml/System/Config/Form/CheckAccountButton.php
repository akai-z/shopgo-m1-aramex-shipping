<?php

class Shopgo_AramexShipping_Block_Adminhtml_System_Config_Form_CheckAccountButton
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        parent::_construct();
        $template = $this->setTemplate('shopgo/aramex_shipping/system/config/check_account_button.phtml');
        if (Mage::registry('aramex_suppliers_data') && Mage::registry('aramex_suppliers_data')->getId()) {
            $template->setData('id', Mage::registry('aramex_suppliers_data')->getId());
        }
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/aramex/checkaccount');
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
            array(
                'id'      => 'aramex_account_checker',
                'label'   => $this->helper('adminhtml')->__('Check Account'),
                'onclick' => 'javascript:shopgoAramexShipping.checkAramexAccount(); return false;'
            )
        );

        return $button->toHtml();
    }
}
