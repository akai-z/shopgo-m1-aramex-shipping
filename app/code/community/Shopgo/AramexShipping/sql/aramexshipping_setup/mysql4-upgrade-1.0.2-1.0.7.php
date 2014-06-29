<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()
    ->addColumn(
        $this->getTable('aramex_shipping_suppliers'),
        'product_type',
        "varchar (30) NULL AFTER `account_pin`"
    );

$installer->getConnection()
    ->addColumn(
        $this->getTable('aramex_shipping_suppliers'),
        'dom_product_type',
        "varchar (30) NULL AFTER `product_type`"
    );

$installer->getConnection()
    ->addColumn(
        $this->getTable('aramex_shipping_suppliers'),
        'dom_customs_value',
        "smallint(6) NULL AFTER `dom_product_type`"
    );

$installer->endSetup();
