<?php

class Shopgo_AramexShipping_Block_Adminhtml_Pickup_Renderer_IncrementId
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        return trim(implode("\n", unserialize($value)));
    }
}
