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
class Lemonline_SVMPaymentModule_Block_Redirectmulti extends Mage_Core_Block_Abstract {

    protected function _toHtml() {
        $model = Mage::getModel("svm/svmPayment");

        $form = new Varien_Data_Form();
        $form->setAction($model->getSvmUrl())
                ->setId("svm_form")
                ->setMethod("POST")
                ->setUseContainer(true);

        foreach ($model->getMultiCheckoutParameters() as $name => $value)
            $form->addField($name, "hidden", array("name" => $name, "value" => $value));

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
                "</body></html>";
        return $html;
    }

}
