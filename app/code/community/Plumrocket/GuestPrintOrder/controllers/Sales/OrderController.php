<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Guest_Print_Order
 * @copyright   Copyright (c) 2014 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */


class Plumrocket_GuestPrintOrder_Sales_OrderController extends Mage_Sales_Controller_Abstract
{

    public function preDispatch()
    {
        parent::preDispatch();

        $action = $this->getRequest()->getActionName();
        if ($action == 'print'){
            return $this;
        }

        $loginUrl = Mage::helper('customer')->getLoginUrl();
        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }


    public function printAction()
    {
        if (Mage::helper('guestprintorder')->moduleEnabled()){
            if (Mage::getSingleton('plumbase/observer')->customer() != Mage::getSingleton('plumbase/product')->currentCustomer()) {
                return;
            }
            if (!$this->_loadPrintValidOrder()) {
                return;
            }
        } else {
            if (!$this->_loadValidOrder()) {
                return;
            }
        }
        $this->loadLayout('print');
        $this->renderLayout();
    }


    protected function _loadPrintValidOrder($orderId = null)
    {
        if (null === $orderId) {
            $orderId = (int) $this->getRequest()->getParam('order_id');
        }
        if (!$orderId) {
            $this->_forward('noRoute');
            return false;
        }

        $order = Mage::getModel('sales/order')->load($orderId);

        if ($order->getCustomerId()) {
            if ($this->_canViewOrder($order)) {
                Mage::register('current_order', $order);
                return true;
            }
        } else if ($this->_canPrintOrder($order)) {
            Mage::register('current_order', $order);
            return true;
        } else {
            $this->_redirect('*/*/history');
        }
        return false;
    }


    protected function _canPrintOrder($order)
    {
        $availableStates = Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates();

        if ($order->getId() && in_array($order->getState(), $availableStates, $strict = true)){
            $remoteIP   = Mage::helper('core/http')->getRemoteAddr();
            $time       = Mage::getModel('core/date')->timestamp() - 86400;

            return ($order->getRemoteIP() == $remoteIP && $order->getCreatedAt() > date('Y-m-d H:i:s', $time));
        }

        return false;
    }
}
