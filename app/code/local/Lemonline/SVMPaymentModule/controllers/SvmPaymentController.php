<?php

/**
 * 
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lemonline.
 *
 * @category   Lemonline
 * @copyright  Copyright (c) 2012 Lemonline (http://www.lemonline.fi)
 * @package controllers
 *
 */
class Lemonline_SVMPaymentModule_SvmPaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Redirect user to Paytrail
     * @return void
     */

    public function redirectAction() {
        try {
            $svmPayment = Mage::getSingleton("svm/svmPayment");
            
            if(Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
                $order = Mage::getModel("sales/order");
                $order->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
                
                if ($svmPayment->getOrderEmailStatus() == 0) {
                    $order->sendNewOrderEmail()->addStatusHistoryComment(
                        Mage::helper('svm')->__('Order confirmation sent.') . " " .
                        Mage::helper('svm')->__('Client redirected to the payment service')
                    )
                    ->setIsCustomerNotified(true)
                    ->save();
                }
                else {
                    $order->addStatusHistoryComment(Mage::helper('svm')->__('Client redirected to the payment service'))
                        ->save();
                }
                
                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                $quote->setIsActive(true)->save();
                Mage::getSingleton('checkout/session')->setQuote($quote);
                Mage::getSingleton('checkout/cart')->setQuote($quote)->save();
            }

            $this->getResponse()->setBody($this->getLayout()->createBlock("svm/redirect")->toHtml());
        } catch(Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('checkout/session')->addError(Mage::helper("svm")->__('An error occured, you have not been charged'));
            $this->_redirect("checkout/onepage/failure", array("_secure" => true));
        }
    }

    /**
     * Redirect user to Paytrail from multishipping checkout
     * @return void
     */
    public function redirectmultiAction() {
        try {
            $svmPayment = Mage::getSingleton("svm/svmPayment");
            $checkout = Mage::getSingleton('checkout/type_multishipping');
            $orderIds = $checkout->getOrderIds();
            if (is_array($orderIds)) {
                $quoteId = null;
                foreach ($checkout->getOrderIds() as $orderId) {
                    $order = Mage::getModel('sales/order');
                    $order->load($orderId);
                    $order->addStatusHistoryComment(Mage::helper('svm')->__('Client redirected to the payment service'));
                    if ($svmPayment->getOrderEmailStatus() == 0) {
                        $order->setEmailSent(true);
                        $order->sendNewOrderEmail();
                    }
                    if($order->getQuoteId()) {
                        $quoteId = $order->getQuoteId();
                    }
                    $order->save();
                }

                if($quoteId) {
                    $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                    $quote->setIsActive(true)->save();
                    Mage::getSingleton('checkout/session')->setQuote($quote);
                    Mage::getSingleton('checkout/cart')->setQuote($quote)->save();
                }
            }

            $this->getResponse()->setBody($this->getLayout()->createBlock("svm/redirectmulti")->toHtml());
        } catch(Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('checkout/session')->addError(Mage::helper("svm")->__('An error occured, you have not been charged'));
            $this->_redirect("checkout/onepage/failure", array("_secure" => true));
        }
    }


    /**
     * Successful payment, user returns to the store
     * @return unknown_type
     */
    public function returnAction() {

        $svmPayment = Mage::getSingleton("svm/svmPayment");
        try {
            $svmPayment->processResponse($this->getRequest()->getParams());
            $this->_redirect("checkout/onepage/success", array("_secure" => true));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_debug();
            $this->_redirect("checkout/cart", array("_secure" => true));
        }
    }

    /**
     * Successful payment, user returns to the store from multishipping checkout
     * @return unknown_type
     */
    public function returnmultiAction() {

        $svmPayment = Mage::getSingleton("svm/svmPayment");
        if ($svmPayment->processMultiResponse($this->getRequest()->getParams())) {
            Mage::getSingleton('checkout/type_multishipping_state')->setActiveStep(
                    Mage_Checkout_Model_Type_Multishipping_State::STEP_SUCCESS
            );
            Mage::getSingleton('checkout/type_multishipping_state')->setCompleteStep(
                    Mage_Checkout_Model_Type_Multishipping_State::STEP_OVERVIEW
            );
            Mage::getSingleton('checkout/type_multishipping')->getCheckoutSession()->clear();
            Mage::getSingleton('checkout/type_multishipping')->getCheckoutSession()->setDisplaySuccess(true);
            $this->_redirect("checkout/multishipping/success", array("_secure" => true));
        } else {
            $this->_debug();
            $this->_redirect("checkout/cart", array("_secure" => true));
        }
    }

    /**
     * Payment canceled by user
     * @return void
     */
    public function cancelAction() {
        $svmPayment = Mage::getSingleton("svm/svmPayment");
        $svmPayment->processCancelResponse($this->getRequest()->getParams());
        $this->_redirect("checkout/cart", array("_secure" => true));
    }

    /**
     * Payment canceled by user from multishipping checkout
     * @return void
     */
    public function cancelmultiAction() {
        $svmPayment = Mage::getSingleton("svm/svmPayment");
        $svmPayment->processMultiCancelResponse($this->getRequest()->getParams());
        $this->_redirect("checkout/cart", array("_secure" => true));
    }

    /**
     * Successful payment, notified by Paytrail
     * @return void
     */
    public function notifyAction() {
        $svmPayment = Mage::getSingleton("svm/svmPayment");
        try {
            if (!$svmPayment->processResponse($this->getRequest()->getParams())) {
                Mage::throwException("Paytrail notify action failed.");
            }
        } catch (Exception $e) {
            Mage::log("Paytrail notify action failed. See exception log for details");
            Mage::logException($e);
            $this->_debug();
            $this->getResponse()->setHeader('HTTP/1.1', '500 Internal Server Error');
        }
    }

    /**
     * 
     */
    protected function _debug()
    {
        $debug = Mage::getStoreConfig("payment/svm/debug");
        if ($debug) {
            Mage::log($this->getRequest()->getParams());
        }
    }
}
