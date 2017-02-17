<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('sales/order'),'mk_order_number', 'text');
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'mk_settled', 'int(11)');

$installer->endSetup();

?>