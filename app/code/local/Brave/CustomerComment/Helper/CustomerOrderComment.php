<?php
class Brave_CustomerComment_Helper_CustomerOrderComment extends Mage_Core_Helper_Abstract
{
    public function setCustomerOrderComment($observer)
    {
        $orderComment = $this->_getRequest()->getPost('braveCustomerOrderComment', false);
        $orderNewsletter = $this->_getRequest()->getPost('yes-newsletter');
        $order = $observer->getEvent()->getOrder();

        if ($orderNewsletter) {
        	// get email and name
        	$email = $order->getCustomerEmail();
        	$name = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        	$mail = new Zend_Mail('UTF-8');
		    $mail->setBodyText($name . ' <' . $email . '> haluaa liittyä uutiskirjeen tilaajaksi.');		    
		    $mail->setFrom('uutiskirjetilaukset@atena.fi', 'Uutiskirjetilaukset');		    
		    $mail->addTo('info@atena.fi', 'Atena Info');		    
		    $mail->setSubject('Uutiskirjetilaus verkkokauppaostoksen yhteydessä');

		    try {
		        $mail->send();
		    }
		    catch(Exception $ex) {
		        Mage::getSingleton('core/session')
		            ->addError(Mage::helper('customer')
		            ->__('Unable to send email.'));
		    }
        }

//        $order->setBraveCustomercomment($orderComment);
        $order->addStatusHistoryComment($orderComment);
//        $order->save();
    }
}