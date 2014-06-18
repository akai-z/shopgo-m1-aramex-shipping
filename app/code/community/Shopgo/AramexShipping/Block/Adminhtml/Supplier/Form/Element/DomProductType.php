<?php

class Shopgo_AramexShipping_Block_Adminhtml_Supplier_Form_Element_DomProductType
    extends Varien_Data_Form_Element_Abstract
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->setType('text');
    }

    public function getElementHtml()
    {
        $elementDisabled = !$this->getValue() ? ' disabled="disabled"' : '';
        $html = '<select id="dom_product_type" class="select"' . $elementDisabled . ' name="dom_product_type">';
        $selected = '';

        $productTypes = Mage::getModel('aramexshipping/system_config_source_domproducttypes')->toOptionArray();
        $currentProductType = $this->getValue()
            ? $this->getValue()
            : Mage::helper('aramexshipping')->getConfigData('dom_product_type', 'carriers_aramex');

        foreach ($productTypes as $productType) {
            if ($productType['value'] == $currentProductType) {
                $selected = ' selected="selected"';
            }

            $html .= '<option value="' . $productType['value'] . '"' . $selected . '>' . $productType['label'] . '</option>';
            $selected = '';
        }

        $useDefaultValue = 1;
        $useDefaultChecked = ' checked="checked"';

        if (!$elementDisabled) {
            $useDefaultValue = 0;
            $useDefaultChecked = '';
        }

        $html .= '</select><br /><input type="checkbox" onclick="toggleValueElements(this, this.parentNode)"' . $useDefaultChecked . ' class="checkbox config-inherit" value="' . $useDefaultValue . '" name="dom_product_type_inherit" id="dom_product_type_inherit" /><label class="inherit" for="dom_product_type_inherit">&nbsp;&nbsp;' . Mage::helper('adminhtml')->__('Use Default') . '</label>';

        return $html;
    }
}
