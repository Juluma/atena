<?php

/**
 * 
 * NOTICE OF LICENSE
 *
 * This source file is released and licensed under a limited, non-exclusive and non-assignable commercial license by Lemonline. 
 *
 * @category   Lemonline
 * @package    Lemonline_SVMPayment_Module
 * @copyright  Copyright (c) 2012 Lemonline (http://www.lemonline.fi)
 * @license    http://www.lemonline.fi/licenses/lemonline-license-1.0.txt  Lemonline License
 */
class Lemonline_SVMPaymentModule_Model_Observer {

    /**
     * 
     * Redirect to payment provider site after orders are created
     * @param $event
     */
    public function redirectMulti($event) {
        $quote = $event->getQuote();
        if ($quote->getData('is_multi_shipping') == 1) {
            $paymentMethodInstance = $quote->getPayment()->getMethodInstance();
            if ($paymentMethodInstance->getCode() == 'svm') {
                $url = $paymentMethodInstance->getMultishippingOrderPlaceRedirectUrl();
                if (!empty($url)) {
                    Mage::app()->getResponse()->setRedirect($url)->sendResponse();
                    exit;
                }
            }
        }
    }

    /**
     * 
     * Set flag for the order for not send email
     * @param $event
     */
    public function setNoEmail($event) {
        $order = $event->getOrder();
        $order->setCanSendNewEmailFlag(false);
    }
    
    /**
     * 
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function cancelPendingOrders(Mage_Cron_Model_Schedule $schedule)
    {
        // Get pending orders collection with Paytrail payment method
        Mage::getModel('svm/svmPayment')->cancelPendingOrders();
    }

}