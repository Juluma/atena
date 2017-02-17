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
class Lemonline_SVMPaymentModule_Model_SvmPayment extends Lemonline_SVMPaymentModule_Model_SvmPayment_Abstract
{
    protected $_code = "svm";
    protected $_canUseForMultishipping = true;
    
    /**
     * Return an url for a payment method image
     * @param int $cols
     * @param bool $horizontal
     * @return unknown_type
     */
    public function getPaymentMethodImageUrl($cols=10, $horizontal=true) {
        $merchant_id = Mage::getStoreConfig("payment/svm/merchant_id");
        $merchant_secure_code = Mage::getStoreConfig("payment/svm/merchant_secure_code");
        $auth = substr(md5($merchant_id . $merchant_secure_code), 0, 16);
        $type = ($horizontal) ? "horizontal" : "vertical";
        $url = "https://img.paytrail.com?id=$merchant_id&type=$type&cols=$cols&auth=$auth";

        return $url;
    }
}
