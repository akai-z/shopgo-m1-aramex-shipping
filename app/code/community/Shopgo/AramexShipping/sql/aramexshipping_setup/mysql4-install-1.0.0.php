<?php

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('aramex_shipping_suppliers')};
CREATE TABLE {$this->getTable('aramex_shipping_suppliers')} (
  `asv_id` int(11) unsigned NOT NULL auto_increment,
  `identifier` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_country_code` varchar(2) NULL,
  `account_entity` varchar(3) NULL,
  `account_number` int(11) unsigned NULL,
  `account_pin` int(11) unsigned NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) NULL,
  `address_line3` varchar(255) NULL,
  `city` varchar(255) NOT NULL,
  `state_or_province_code` varchar(255) NULL,
  `post_code` varchar(255) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `department` varchar(255) NULL,
  `person_name` varchar(255) NOT NULL,
  `person_title` varchar(255) NULL,
  `company_name` varchar(255) NOT NULL,
  `phone_number1` varchar(255) NOT NULL,
  `phone_number1_ext` varchar(255) NOT NULL,
  `phone_number2` varchar(255) NULL,
  `phone_number2_ext` varchar(255) NULL,
  `fax_number` varchar(255) NULL,
  `cellphone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `type` varchar(255) NULL,
  PRIMARY KEY (`asv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
if (!$setup->getAttributeId(Mage_Catalog_Model_Product::ENTITY, 'aramex_supplier')) {
    $setup->addAttribute('catalog_product', 'aramex_supplier', array(
        'attribute_set'              => 'Default',
        'group'                      => 'General',
        'input'                      => 'select',
        'type'                       => 'text',
        'label'                      => 'Aramex Supplier',
        'backend'                    => '',
        'visible'                    => 1,
        'required'                   => 0,
        'user_defined'               => 1,
        'searchable'                 => 1,
        'filterable'                 => 0,
        'comparable'                 => 1,
        'visible_on_front'           => 1,
        'visible_in_advanced_search' => 0,
        'is_html_allowed_on_front'   => 0,
        'source'                     => 'aramexshipping/product_attribute_source_suppliers',
        'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL
    ));
}

if (version_compare(Mage::getVersion(), '1.4.1.0', '>=')) {
    $setup = $this->_conn;
    $setup->addColumn($this->getTable('sales_flat_shipment'), 'aramex_shipment_data', 'text');
} else {
    $setup = new Mage_Eav_Model_Entity_Setup('sales_setup');
    $setup->addAttribute('shipment', 'aramex_shipment_data', array('type' => 'text'));
}

$installer->endSetup();
