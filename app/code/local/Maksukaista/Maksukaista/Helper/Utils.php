<?php

class Maksukaista_Maksukaista_Helper_Utils  extends Mage_Core_Helper_Abstract
{
  public static function addOrderIdPrefix($orderId)
  {
    $orderIdPrefix = Mage::getStoreConfig('payment/maksukaista/orderid_prefix');
    return $orderIdPrefix . "_" . $orderId;
  }

  public static function removeOrderIdPrefix($orderId)
  {
    $orderIdPrefix = Mage::getStoreConfig('payment/maksukaista/orderid_prefix');
    $replaceCount = 1;
    return str_replace($orderIdPrefix . "_" , "", $orderId, $replaceCount);
  }
}