<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()
    ->addColumn(
        $this->getTable('aramex_shipping_suppliers'),
        'cod_account_number',
        "int(11) unsigned NULL AFTER `account_pin`"
    );

$installer->getConnection()
    ->addColumn(
        $this->getTable('aramex_shipping_suppliers'),
        'cod_account_pin',
        "int(11) unsigned NULL AFTER `cod_account_number`"
    );

$installer->endSetup();
