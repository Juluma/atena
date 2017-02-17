<?php

/**
 * 
 * NOTICE OF LICENSE
 *
 * This source file is released and licensed under a limited, non-exclusive and non-assignable commercial license by Lemonline. 
 *
 * @category   Lemonline
 * @package    Lemonline_SVMPaymentModule
 * @copyright  Copyright (c) 2012 Lemonline (http://www.lemonline.fi)
 * @license    http://www.lemonline.fi/licenses/lemonline-license-1.0.txt  Lemonline License
 */
class Lemonline_SVMPaymentModule_Model_SvmPayment_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_formBlockType = "svm/form";
    protected $_formTemplate = "svm/form.phtml";
    protected $_paymentMethodImage = "";
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_preselectedMethod;
    protected $_forceUseAdvanced = false;

    
    /**
     * Return the preseleceted payment method code
     * 
     * @return int $this->_preselectedMethod
     */
    protected function _getPreselectedMethod() {
        return $this->_preselectedMethod;
    }
    /**
     * (non-PHPdoc)
     * @see app/code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract#assignData($data)
     */
    public function assignData($data) {
        if(is_object($data)) {
            $checkout = $this->getCheckout();
            $checkout->setSvmPm($data->getPm());
            $checkout->setSvmPreselectedMethod($data->getPreselectedMethod());
        }
        return $this;
    }
    
    /**
     * 
     * @return boolean
     */
    protected function _getUseAdvanced()
    {
        if ($this->_forceUseAdvanced) {
            return true;
        }
        if (Mage::getStoreConfig("payment/svm/use_advanced")) {
            return true;
        }
        return false;
    }

    /**
     * 
     * @param string $name
     * @return void
     */
    public function createFormBlock($name) {
        $block = $this->getLayout()->createBlock($this->_formBlockType, $name)
                ->setMethod("form")
                ->setPayment($this->getPayment())
                ->setTemplate($this->_formTemplate);
    }

    /**
     * Return an url for a payment method image
     * @param int $cols
     * @param bool $horizontal
     * @return unknown_type
     */
    public function getPaymentMethodImage() {
        return $this->_paymentMethodImage;
    }

    /**
     * Get new order email status
     * @return int
     */
    public function getOrderEmailStatus() {
        return Mage::getStoreConfig('payment/svm/order_email_status');
    }

    /**
     * Get new invoice email status
     * @return int
     */
    public function getInvoiceEmailStatus() {
        return Mage::getStoreConfig('payment/svm/invoice_email_status');
    }

    /**
     * Get the current checkout session
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton("checkout/session");
    }

    /**
     * Get the current customer session
     * @return Mage_Customer_Model_Session
     */
    public function getCustomerSession() {
        return Mage::getSingleton("customer/session");
    }
    
    /**
     * Check if bypass is used
     */
    public function getBypassStatus() {
        if($this->_getPreselectedMethod()) {
            return 2;
        }
        return 1;
    }

    /**
     * Get parameters for the redirect action 
     * @return array
     */
    public function getMultiCheckoutParameters() {
        $checkout = $checkout = Mage::getSingleton('checkout/type_multishipping');
        $orderIds = $checkout->getOrderIds();
        $amount = 0;
        $orderNumber = '';
        $a = null;
        $i = 0;
        $orderDescription = '';
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order');
            $order->load($orderId);
            if ($i == 0) {
                $orderNumber = $order->getRealOrderId();
                $orderDescription = $orderNumber;
                $a = $order->getBillingAddress();
            } else {
                $orderDescription .= ", " . $order->getRealOrderId();
            }
            $amount += $order->getBaseGrandTotal();
            $i++;
        }

        $params = array(
            "MERCHANT_ID" => Mage::getStoreConfig("payment/svm/merchant_id"),
            "AMOUNT" => sprintf('%.2f', $amount),
            "ORDER_NUMBER" => $orderNumber,
            "ORDER_DESCRIPTION" => $orderDescription,
            "CURRENCY" => "EUR",
            "RETURN_ADDRESS" => Mage::getUrl("svm/svmPayment/returnmulti", array("_secure" => true)),
            "CANCEL_ADDRESS" => Mage::getUrl("svm/svmPayment/cancelmulti", array("_secure" => true)),
            //"NOTIFY_ADDRESS"	=> Mage::getUrl("svm/svmPayment/notifymulti", array("_secure" => true)),
            "TYPE" => "S1",
            "CULTURE" => Mage::getStoreConfig("payment/svm/lang"),
            "CONTACT_TELNO" => $a->getTelephone(),
            "CONTACT_CELLNO" => $a->getTelephone(),
            "CONTACT_EMAIL" => $a->getEmail(),
            "CONTACT_FIRSTNAME" => $a->getFirstname(),
            "CONTACT_LASTNAME" => $a->getLastname(),
            "CONTACT_COMPANY" => $a->getCompany(),
            "CONTACT_ADDR_STREET" => implode(', ', $a->getStreet()),
            "CONTACT_ADDR_ZIP" => $a->getPostcode(),
            "CONTACT_ADDR_CITY" => $a->getCity(),
            "CONTACT_ADDR_COUNTRY" => $a->getCountry(),
                //"INCLUDE_VAT"		=> 0,
                //"ITEMS"				=> count($this->getQuote()->getAllItems())
        );

        // Remove disallowed characters
        foreach ($params as $key => $param) {
            $params[$key] = str_replace('|', '-', $param);
        }

        // Calculate the Authcode
        $authstring = Mage::getStoreConfig("payment/svm/merchant_secure_code");

        $authstring .= "|" . $params["MERCHANT_ID"];
        $authstring .= "|" . $params["AMOUNT"];
        $authstring .= "|" . $params["ORDER_NUMBER"];
        $authstring .= "|"; // REFERENCE_NUMBER
        $authstring .= "|" . $params["ORDER_DESCRIPTION"];
        $authstring .= "|" . $params["CURRENCY"];
        $authstring .= "|" . $params["RETURN_ADDRESS"];
        $authstring .= "|" . $params["CANCEL_ADDRESS"];
        $authstring .= "|"; //.$params["PENDING_ADDRESS"];
        $authstring .= "|"; //.$params["NOTIFY_ADDRESS"];
        $authstring .= "|" . $params["TYPE"];
        $authstring .= "|" . $params["CULTURE"];
        $authstring .= "|"; //.$params["PRESELECTED_METHOD"];
        $authstring .= "|"; //.$params["MODE"];
        $authstring .= "|"; //.$params["VISIBLE_METHODS"];
        $authstring .= "|"; //.$params["GROUP"];

        $authstring = strtoupper(md5($authstring));
        $params['AUTHCODE'] = $authstring;

        return $params;
    }

    /**
     * Get parameters for the redirect action 
     * @return array
     */
    public function getCheckoutParameters() {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        $a = $order->getBillingAddress();
        $b = $order->getShippingAddress();
        if(!$b) $b = $a;
        
        if ($order->getBaseCurrencyCode() != "EUR")
            Mage::throwException(Mage::helper("svm")->__("Currently only EUR payments are supported by the SVMPaymentModule."));

        $checkout = $this->getCheckout();
        $amount = $order->getBaseGrandTotal();
        $amountFormatted = sprintf('%.2f', round($amount, 2));
        $calculatedTotal = 0;
        //Sum of discount
        $discount = $order->getBaseDiscountAmount();
        if ($discount > 0) {
            $discount = 0 - $discount;
        }
        $tax = Mage::getModel('tax/calculation');
        $rateRequest = $tax->getRateRequest($b, $a);
        $rateRequest->setProductClassId(Mage::helper('tax')->getShippingTaxClass($this->getStore()));
        $shippingTaxRate = $tax->getRate($rateRequest);
        $shippingAmount = $order->getBaseShippingAmount() + $order->getBaseShippingTaxAmount();
        $email = $order->getCustomerEmail();
        $items = $order->getAllItems();
        
        $type = "E1";
        if(!$this->_getUseAdvanced()) {
            $type = "S1";
        }

        $params = array(
            "MERCHANT_ID" => Mage::getStoreConfig("payment/svm/merchant_id"),
            "ORDER_NUMBER" => $checkout->getLastRealOrderId(),
            "REFERENCE_NUMBER" => $this->getPaymentReference($checkout->getLastRealOrderId()),
            "CURRENCY" => "EUR",
            "RETURN_ADDRESS" => Mage::getUrl("svm/svmPayment/return", array("_secure" => true)),
            "CANCEL_ADDRESS" => Mage::getUrl("svm/svmPayment/cancel", array("_secure" => true)),
            "NOTIFY_ADDRESS" => Mage::getUrl("svm/svmPayment/notify", array("_secure" => true)),
            "TYPE" => $type,
            "CULTURE" => Mage::getStoreConfig("payment/svm/lang"),
            "CONTACT_TELNO" => $a->getTelephone(),
            "CONTACT_CELLNO" => $a->getTelephone(),
            "CONTACT_EMAIL" => $email,
            "CONTACT_FIRSTNAME" => $a->getFirstname(),
            "CONTACT_LASTNAME" => $a->getLastname(),
            "CONTACT_COMPANY" => $a->getCompany(),
            "CONTACT_ADDR_STREET" => implode(', ', $a->getStreet()),
            "CONTACT_ADDR_ZIP" => $a->getPostcode(),
            "CONTACT_ADDR_CITY" => $a->getCity(),
            "CONTACT_ADDR_COUNTRY" => $a->getCountry(),
            "INCLUDE_VAT" => 1,
            "PRESELECTED_METHOD" => $this->_getPreselectedMethod(),
            "MODE" => $this->getBypassStatus(),
//			"VISIBLE_METHODS" 	=> ,
//			"GROUP"				=> ,
//			"PENDING_ADDRESS"	=>
        );
        
        if($type == "S1") {
            $params["AMOUNT"] = $amountFormatted;
        }
        
        if($type == "E1") {
            $includesTax = Mage::helper('tax')->priceIncludesTax();
            // Add product rows to the request data
            $i = 0;
            foreach ($items as $item) {
                if (Mage::getStoreConfig("payment/svm/send_child_products")) {
                    if ($item->getHasChildren()) {                 
                        continue;
                    }
                } else {
                    if ($item->getParentItem()) {
                        continue;
                    }
                }
                $qty = $item->getQty();
                if(empty($qty)) $qty = $item->getQtyOrdered();
                if(empty($qty)) $qty = 1;
                $qty = round($qty, 2);
                $params["ITEM_TITLE[$i]"] = trim($item->getName());//trim($item->getQty() . " x " . $item->getName());
                $params["ITEM_NO[$i]"] = mb_substr($item->getSku(), 0, 16, "UTF-8"); // Limit sku to 16 characters
                $params["ITEM_AMOUNT[$i]"] = $qty; //1;
                if (version_compare(Mage::getVersion(), '1.4.1.0', '>=')) {
                    $params["ITEM_PRICE[$i]"] = sprintf('%.2f', round($item->getBaseRowTotalInclTax() / $qty, 2));
                } else {
                    $params["ITEM_PRICE[$i]"] = sprintf('%.2f', round(($item->getBaseRowTotal() + $item->getBaseTaxAmount()) / $qty /* (1+($item->getTaxPercent()/100)) */, 2));
                }
                $params["ITEM_TAX[$i]"] = sprintf('%.2f', round($item->getTaxPercent(), 2));
                if ($params["ITEM_TAX[$i]"] == 0 and $item->getTaxAmount() > 0) {
                    $params["ITEM_TAX[$i]"] = sprintf('%.2f', round($item->getBaseTaxAmount()/$item->getQtyOrdered()/$item->getBasePrice(), 2)*100);
                }
                $params["ITEM_DISCOUNT[$i]"] = sprintf('%.2f', round($item->getDiscountPercent(), 2));//'0';
                $params["ITEM_TYPE[$i]"] = '1';
                $calculatedTotal += $params["ITEM_PRICE[$i]"]*$qty;
                $calculatedTotal -= (($params["ITEM_PRICE[$i]"]*$params["ITEM_DISCOUNT[$i]"]) / 100) * $qty;
                $i++;
            }

            // Add shipping row

            if (!empty($shippingAmount)) {
                $params["ITEM_TITLE[$i]"] = trim($order->getShippingDescription());
                $params["ITEM_NO[$i]"] = '0';
                $params["ITEM_AMOUNT[$i]"] = '1';
                $params["ITEM_PRICE[$i]"] = sprintf('%.2f', round($shippingAmount, 2));
                $params["ITEM_TAX[$i]"] = sprintf('%.2f', round($shippingTaxRate, 2));
                $params["ITEM_DISCOUNT[$i]"] = '0';
                $params["ITEM_TYPE[$i]"] = '2';
                $calculatedTotal += $params["ITEM_PRICE[$i]"];
                $i++;
            }

            // Add disount row
            /*
            if (!empty($discount) && $discount > 0) {
                $params["ITEM_TITLE[$i]"] = Mage::helper('svm')->__("Discount");
                $params["ITEM_NO[$i]"] = '0';
                $params["ITEM_AMOUNT[$i]"] = '1';
                $params["ITEM_PRICE[$i]"] = sprintf('%.2f', round($discount, 2));
                $params["ITEM_TAX[$i]"] = '0';
                $params["ITEM_DISCOUNT[$i]"] = '0';
                $params["ITEM_TYPE[$i]"] = '1';
                $calculatedTotal += $params["ITEM_PRICE[$i]"];
                $i++;
            }*/

            // Add a correction row if the calculated is not equals to real grand total
            $correction = $amount - $calculatedTotal;
            $correction = sprintf('%.2f', round($correction, 2));
            if ($correction != 0) {
                $params["ITEM_TITLE[$i]"] = Mage::helper('svm')->__("Correction");
                $params["ITEM_NO[$i]"] = '0';
                $params["ITEM_AMOUNT[$i]"] = '1';
                $params["ITEM_PRICE[$i]"] = $correction;
                $params["ITEM_TAX[$i]"] = '0';
                $params["ITEM_DISCOUNT[$i]"] = '0';
                $params["ITEM_TYPE[$i]"] = '1';
                $i++;
            }

            // Amount of items
            $params["ITEMS"] = $i;
        }

        // Remove disallowed characters
        foreach ($params as $key => $param) {
            $params[$key] = str_replace('|', '-', $param);
        }

        // Calculate the Authcode
        $authstring = Mage::getStoreConfig("payment/svm/merchant_secure_code");

        $authstring .= "|" . $params["MERCHANT_ID"];
        if($type == "S1") {
            $authstring .= "|" . $params["AMOUNT"];
        }
        $authstring .= "|" . $params["ORDER_NUMBER"];
        if(isset($params["REFERENCE_NUMBER"])) {
            $authstring .= "|" . $params["REFERENCE_NUMBER"];
        } else {
            $authstring .= "|"; // REFERENCE_NUMBER
        }
        $authstring .= "|"; // ORDER_DESCRIPTION
        $authstring .= "|" . $params["CURRENCY"];
        $authstring .= "|" . $params["RETURN_ADDRESS"];
        $authstring .= "|" . $params["CANCEL_ADDRESS"];
        $authstring .= "|"; //.$params["PENDING_ADDRESS"];
        $authstring .= "|" . $params["NOTIFY_ADDRESS"];
        $authstring .= "|" . $params["TYPE"];
        $authstring .= "|" . $params["CULTURE"];        
        $authstring .= "|" . $params["PRESELECTED_METHOD"];
        $authstring .= "|" . $params["MODE"];
        $authstring .= "|"; //.$params["VISIBLE_METHODS"];
        $authstring .= "|"; //.$params["GROUP"];
        
        if($type == "E1") {
            $authstring .= "|" . $params["CONTACT_TELNO"];
            $authstring .= "|" . $params["CONTACT_CELLNO"];
            $authstring .= "|" . $params["CONTACT_EMAIL"];
            $authstring .= "|" . $params["CONTACT_FIRSTNAME"];
            $authstring .= "|" . $params["CONTACT_LASTNAME"];
            $authstring .= "|" . $params["CONTACT_COMPANY"];
            $authstring .= "|" . $params["CONTACT_ADDR_STREET"];
            $authstring .= "|" . $params["CONTACT_ADDR_ZIP"];
            $authstring .= "|" . $params["CONTACT_ADDR_CITY"];
            $authstring .= "|" . $params["CONTACT_ADDR_COUNTRY"];
            $authstring .= "|" . $params["INCLUDE_VAT"];
            if(isset($params["ITEMS"])) {
                $authstring .= "|" . $params["ITEMS"];
            }

            foreach ($params as $key => $param) {
                if (strpos($key, 'ITEM_') === 0)
                    $authstring .= "|" . $param;
            }
        }
        
        $authstring = strtoupper(md5($authstring));
        $params['AUTHCODE'] = $authstring;
        return $params;
    }

    /**
     * Get Paytrail gateway URL
     * @return string
     */
    public function getSvmUrl() {
        return "https://payment.paytrail.com/";
    }

    /**
     * Get the URL for redirect action
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl("svm/svmPayment/redirect", array("_secure" => true));
    }

    /**
     * Get the URL for multishipping redirect action
     * @return string
     */
    public function getMultishippingOrderPlaceRedirectUrl() {
        return Mage::getUrl("svm/svmPayment/redirectmulti", array("_secure" => true));
    }

    /**
     * Get the current quote
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    /**
     * (non-PHPdoc)
     * @see app/code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract#isAvailable($quote)
     */
    public function isAvailable($quote=null) {
        if (is_null($quote)) {
            return false;
        }
        if($quote->getGrandTotal() < Mage::getStoreConfig('payment/svm/minimum_sum')) { 
            return false; }
        if (!$this->getConfigData('active')) {
            return false;
        }
        return parent::isAvailable($quote);
    }

    /**
     * Get an order by params
     * @param array $params
     * @return Mage_Core_Model_Sales_Order
     */
    public function getOrder($params) {
        if (!isset($params["ORDER_NUMBER"])) {
            Mage::throwException("Order number is not set");
        } else {
            $id = (int) $params["ORDER_NUMBER"];
            $order = Mage::getModel("sales/order");
            $order->loadByIncrementId($id);
            if (!$order->getId()) {
                Mage::throwException("Could not load order by id: " . $id);
            }
            
            return $order;
        }
    }

    /**
     * Cancel an order
     * @param $order
     * @return void
     */
    public function cancelOrder($order) {
        if (!$order->canCancel())
            return false;

        $order->cancel();
        $this->getCheckout()->addNotice(Mage::helper("svm")->__('The order was canceled, you have not been charged'));
        $order->save();
        return true;
    }

    /**
     * Process cancel response
     * @param unknown_type $params
     * @return bool
     */
    public function processCancelResponse($params) {
        if (!$order = $this->getOrder($params))
            return false;

        $authstring = $params["ORDER_NUMBER"];
        $authstring .= "|" . $params["TIMESTAMP"];
        $authstring .= "|" . Mage::getStoreConfig("payment/svm/merchant_secure_code");

        $authstring = strtoupper(md5($authstring));

        if ($authstring == $params["RETURN_AUTHCODE"])
            return $this->cancelOrder($order);
    }

    /**
     * Process cancel response from multishipping checkout
     * @param unknown_type $params
     * @return bool
     */
    public function processMultiCancelResponse($params) {
        $checkout = $checkout = Mage::getSingleton('checkout/type_multishipping');
        $orderIds = $checkout->getOrderIds();
        if (!is_array($orderIds))
            return false;

        $authstring = $params["ORDER_NUMBER"];
        $authstring .= "|" . $params["TIMESTAMP"];
        $authstring .= "|" . Mage::getStoreConfig("payment/svm/merchant_secure_code");

        $authstring = strtoupper(md5($authstring));

        $canceled = false;
        if ($authstring == $params["RETURN_AUTHCODE"]) {
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order');
                $order->load($orderId);
                if (!$order->canCancel())
                    continue;
                $order->cancel();
                $order->save();
                $canceled = true;
            }
        }
        if ($canceled) {
            $this->getCheckout()->addNotice(Mage::helper("svm")->__('The order was canceled, you have not been charged'));
        }
        return $canceled;
    }

    /**
     * Process normal response
     * @param array $params
     * @return bool
     */
    public function processResponse($params) {
        $order = $this->getOrder($params);

        if (!isset($params["PAID"])) {
            Mage::throwException("PAID parameter not set.");
        }

        $id = $params["ORDER_NUMBER"];
        $timestamp = $params["TIMESTAMP"];
        $transaction_id = $params["PAID"];
        $payment_method = $params["METHOD"];
        $return_authcode = $params["RETURN_AUTHCODE"];

        $authstring = $id;
        $authstring .= "|" . $timestamp;
        $authstring .= "|" . $transaction_id;
        $authstring .= "|" . $payment_method;
        $authstring .= "|" . Mage::getStoreConfig("payment/svm/merchant_secure_code");

        $authstring = strtoupper(md5($authstring));

        if ($authstring != $return_authcode) {
            // verification failed
            $order->addStatusToHistory(
                $order->getStatus(), Mage::helper('svm')->__('Paytrail verification failed'), $notified = true
            );
            $this->getCheckout()->addNotice(Mage::helper("svm")->__('Paytrail verification failed'));

            Mage::throwException("Paytrail authcode verification failed.");
        }
        
        // If order is canceled, reopen
        if ($order->isCanceled()) {
            $this->_uncancelOrder($order);
        }
        
        if (!$order->canInvoice()) {
            // Order already captured
            return true;
        }
        $order->getPayment()->setTransactionId($transaction_id);
        $payment_methods = $this->_getPaymentMethods();

        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        if ($this->getInvoiceEmailStatus() != 1) {
            $invoice->setEmailSent(true);
            $invoice->sendEmail();
        }
        
        $invoice->save();
        
        $newOrderStatus = 'processing';
        $notify = ($this->getOrderEmailStatus() == 1) ? true : false;
        if (isset($payment_methods[$payment_method])) {
            $paymentDescription = Mage::helper('svm')->__('Received Paytrail verification. %s payment method was used.', $payment_methods[$payment_method]);
        } else {
            $paymentDescription = Mage::helper('svm')->__('Received Paytrail verification. Unknown payment method was used.');
        }
        
        $order->setState(
            Mage_Sales_Model_Order::STATE_PROCESSING, $newOrderStatus, $paymentDescription, $notify
        )->save();
        
        if ($notify) {
            $order->sendNewOrderEmail()->addStatusHistoryComment(
                Mage::helper('svm')->__('Order confirmation sent.')
            )
            ->setIsCustomerNotified(true)
            ->save();
        }
        
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        
        return true;
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Order $order
     */
    protected function _uncancelOrder(Mage_Sales_Model_Order $order)
    {
        $transaction = Mage::getSingleton('core/resource')->getConnection('core_write');
        try {
            $transaction->beginTransaction();
            $order->setState($this->getNewOrderState(), $this->getNewOrderStatus());
            $order->save();
            foreach ($order->getAllItems() as $item) {
                $item->setQtyCanceled(0);
                $item->save();
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
            Mage::logException($e);
        }
    }
    
    /**
     * 
     * @return string
     */
    public function getNewOrderState()
    {
        return Mage_Sales_Model_Order::STATE_NEW;
    }
    
    /**
     * 
     * @return string
     */
    public function getNewOrderStatus()
    {
        return Mage::getStoreConfig("payment/svm/order_status");
    }
    
    /**
     * 
     * @return array
     */
    protected function _getPaymentMethods()
    {
        return array(
            0   => '',
            1   => 'Nordea',
            2   => 'Osuuspankki',
            3   => 'Danske Bank',
            4   => 'Tapiola',
            5   => 'Ålandsbanken',
            6   => 'Handelsbanken',
            7   => 'Säästöpankit, paikallisosuuspankit, Aktia, Nooa',
            8   => 'Luottokunta',
            9   => 'Paypal',
            10  => 'S-Pankki',
            11  => 'Klarna, Laskulla',
            12  => 'Klarna, Osamaksulla',
            13  => 'Collector',
            14  => '',
            15  => '',
            16  => '',
            17  => '',
            18  => 'Joustoraha',
            19  => 'Collector',
            30  => 'Visa',
            31  => 'MasterCard',
            32  => 'Maestro',
            33  => 'American Express',
            34  => 'Diners Club',
            35  => 'JCB',
            50  => 'Aktia',
            51  => 'POP Pankki',
            52  => 'Säästöpankki',
            53  => 'Visa, Nets',
            54  => 'MasterCard, Nets'
        );
    }

    public function processMultiResponse($params) {
        $checkout = $checkout = Mage::getSingleton('checkout/type_multishipping');
        $orderIds = $checkout->getOrderIds();
        if (!is_array($orderIds))
            return false;

        if (!isset($params["PAID"])) {
            $this->getCheckout()->addNotice(Mage::helper("svm")->__('Paytrail verification failed'));
            return false;
        }

        $id = $params["ORDER_NUMBER"];
        $timestamp = $params["TIMESTAMP"];
        $transaction_id = $params["PAID"];
        $payment_method = $params["METHOD"];
        $return_authcode = $params["RETURN_AUTHCODE"];

        $authstring = $id;
        $authstring .= "|" . $timestamp;
        $authstring .= "|" . $transaction_id;
        $authstring .= "|" . $payment_method;
        $authstring .= "|" . Mage::getStoreConfig("payment/svm/merchant_secure_code");

        $authstring = strtoupper(md5($authstring));

        if (!$authstring == $return_authcode) { // Auth string failed
            $this->getCheckout()->addNotice(Mage::helper("svm")->__('Paytrail verification failed'));
            return false;
        }

        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order');
            $order->load($orderId);
            
            // If the order is canceled, reopen
            if ($order->isCanceled()) {
                $this->_uncancelOrder($order);
            }
            
            if (!$order->canInvoice()) {
                // Order already captured
                continue;
            }
            $order->getPayment()->setTransactionId($transaction_id);
            $invoice = $order->prepareInvoice();
            $invoice->register()->pay();
            $invoice->save();

            $payment_methods = $this->_getPaymentMethods();

            $newOrderStatus = 'processing';
            $notify = ($this->getOrderEmailStatus() == 1) ? true : false;
            if (isset($payment_methods[$payment_method])) {
                $paymentDescription = Mage::helper('svm')->__('Received Paytrail verification. %s payment method was used.', $payment_methods[$payment_method]);
            } else {
                $paymentDescription = Mage::helper('svm')->__('Received Paytrail verification. Unknown payment method was used.');
            }
            $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING, $newOrderStatus, $paymentDescription, $notify
            );
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

            if ($notify) {
                $order->setEmailSent(true);
                $order->sendNewOrderEmail();
            }

            if ($this->getInvoiceEmailStatus() != 1) {
                $invoice->setEmailSent(true);
                $invoice->sendEmail();
                $invoice->save();
            }

            $order->save();
        }

        return true;
    }
    
    /**
     * Generate a bank reference number by order id
     * 
     * @param type $orderId
     * @return int 
     */
    public function getPaymentReference($orderId) {
        $ref = $orderId;

        // generate the verification number
        $verification_number = 0;
        $factor = 7;
        for ($i = strlen($ref) - 1; $i >= 0; $i--) {
            $verification_number += $ref[$i] * $factor;
            if ($factor == 7)
                $factor = 3; else if ($factor == 3)
                $factor = 1; else if ($factor == 1)
                $factor = 7;
        }
        $next_ten = $verification_number;
        while ($next_ten % 10 != 0) {
            $next_ten++;
        }
        $verification_number = $next_ten - $verification_number;
        if ($verification_number == 10)
            $verification_number = 0;

        $ref .= $verification_number;

        return $ref;
    }
    
    /**
     * 
     */
    public function cancelPendingOrders()
    {
        if (!Mage::getStoreConfig("payment/svm/cancel_orders")) {
            return false;
        }
        
        $lifetime = intval(Mage::getStoreConfig("payment/svm/cancel_orders_lifetime"));

        if ($lifetime <= 0) {
            return false;
        }
        
        $orders = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('status', $this->getNewOrderStatus());
        
        foreach ($orders as $order) {
            // If the order is not using Paytrail as a payment method, continue
            if ($order->getPayment()->getMethodInstance()->getCode() != $this->_code) {
                continue;
            }
            
            if (!$order->canCancel()) {
                continue;
            }

            $now = new DateTime();
            $createdAt = new DateTime($order->getCreatedAt());
            $interval = $now->diff($createdAt);
            $days = $interval->format('%a');
            if ($days >= $lifetime) {
                $order->cancel();
                $order->addStatusHistoryComment(Mage::helper('svm')->__('Order canceled automatically after %s days', $lifetime));
                $order->save();
            }
        }
    }

}
