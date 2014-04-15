<?php

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('aramex_shipping_pickups')};
CREATE TABLE {$this->getTable('aramex_shipping_pickups')} (
  `asp_id` int(11) unsigned NOT NULL auto_increment,
  `guid` varchar(255) NOT NULL,
  `shipment_increment_ids` text NOT NULL,
  `supplier_id` int(11) unsigned NOT NULL Default '0',
  PRIMARY KEY (`asp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();
