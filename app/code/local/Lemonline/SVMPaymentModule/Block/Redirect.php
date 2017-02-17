<?php

/**
 * 
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lemonline.
 *
 * @category   Lemonline
 * @copyright  Copyright (c) 2012 Lemonline (http://www.lemonline.fi)
 *
 */
class Lemonline_SVMPaymentModule_Block_Redirect extends Mage_Core_Block_Abstract
{
    /**
     * 
     * @return string
     */
    protected function _toHtml() {
        $model = Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethodInstance();

        $form = new Varien_Data_Form();
        
        $form->setAction($model->getSvmUrl())
                ->setId("svm_form")
                ->setMethod("POST")
                ->setUseContainer(true);

        foreach ($model->getCheckoutParameters() as $name => $value) {
            $form->addField($name, "hidden", array("name" => $name, "value" => $value));
        }
        
        if (!Mage::getStoreConfig("payment/svm/bypass_svm")):
            $redirect = $this->__("You will be redirected to the Paytrail website in a few seconds.");
        else:
            $redirect = $this->__("You will be redirected to the selected payment service in a few seconds.");
        endif;
        $html = "<html>" .
                "<head>" .
                "<title>Paytrail Redirection Page</title>" .
                "</head><body>" .
                $redirect .
                $form->toHtml() .
                "<script type='text/javascript'>document.getElementById('svm_form').submit();</script>" .
                "<button type=\"button\" onclick=\"document.getElementById('svm_form').submit();\">".
                $this->__("If you are not getting redirecetd, Continue here").
                "</button>".
                "</body></html>";
        return $html;
    }
}
