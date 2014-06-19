<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('aramex_shipping_suppliers')} ADD `product_type` varchar(30) NULL AFTER `account_pin`;
ALTER TABLE {$this->getTable('aramex_shipping_suppliers')} ADD `dom_product_type` varchar(30) NULL AFTER `product_type`;
ALTER TABLE {$this->getTable('aramex_shipping_suppliers')} ADD `dom_customs_value` smallint(6) NULL AFTER `dom_product_type`;

    ");

$installer->endSetup();
