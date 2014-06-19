<?php

class Shopgo_AramexShipping_Block_Adminhtml_Supplier_Form_Element_DomCustomsValue
    extends Varien_Data_Form_Element_Abstract
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->setType('select');
    }

    public function getElementHtml()
    {
        $elementDisabled = !$this->getValue() ? ' disabled="disabled"' : '';
        $html = '<select id="dom_customs_value" class="select"' . $elementDisabled . ' name="dom_customs_value">';
        $selected = '';

        $enabledDisable = Mage::getModel('adminhtml/system_config_source_enabledisable')->toOptionArray();
        $currentStatus = $this->getValue()
            ? $this->getValue()
            : Mage::helper('aramexshipping')->getConfigData('dom_customs_value', 'carriers_aramex');

        foreach ($enabledDisable as $answer) {
            if ($answer['value'] == $currentStatus) {
                $selected = ' selected="selected"';
            }

            $html .= '<option value="' . $answer['value'] . '"' . $selected . '>' . $answer['label'] . '</option>';
            $selected = '';
        }

        $useDefaultValue = 1;
        $useDefaultChecked = ' checked="checked"';

        if (!$elementDisabled) {
            $useDefaultValue = 0;
            $useDefaultChecked = '';
        }

        $html .= '</select><br /><input type="checkbox" onclick="toggleValueElements(this, this.parentNode)"' . $useDefaultChecked . ' class="checkbox config-inherit" value="' . $useDefaultValue . '" name="dom_customs_value_inherit" id="dom_customs_value_inherit" /><label class="inherit" for="dom_customs_value_inherit">&nbsp;&nbsp;' . Mage::helper('adminhtml')->__('Use Default') . '</label>';

        return $html;
    }
}
