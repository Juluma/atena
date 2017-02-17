<?php

class ReturnCodes
{
  const Success =0;
  const Failed = 1;
  const BankError = 4;
  const Maintenance = 10;
}

class Maksukaista_Maksukaista_PaymentController extends Mage_Core_Controller_Front_Action 
{
  public function redirectAction()
  {
    $order = Mage::getModel('sales/order');
    $order->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
  
    $orderId = Maksukaista_Maksukaista_Helper_Utils::addOrderIdPrefix($order->getIncrementId());
    $orderId = $orderId . '_' . time();
    $order->setMkOrderNumber($orderId);
    $order->save();

    $this->loadLayout();
    $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('maksukaista/standard_redirect'));
    $this->renderLayout();
  }
  public function notifyAction()
  {
    $returnCode = isset($_GET['RETURN_CODE']) ? $_GET['RETURN_CODE'] : '';
    $orderNumber = $_GET['ORDER_NUMBER'];
    $authCode = $_GET['AUTHCODE'];
    $incidentId = isset($_GET['INCIDENT_ID']) ? $_GET['INCIDENT_ID'] : '';
    $settled =  isset($_GET['SETTLED']) ? $_GET['SETTLED'] : '';
    $orderId = isset($_GET['id']) ? $_GET['id'] : '';
    $merchantPrivateKey =  Mage::getStoreConfig('payment/maksukaista/merchant_private_key');
    $authCodeConfirm = $returnCode.'|'.$orderNumber;

    if(isset($_GET['SETTLED'])){
      $authCodeConfirm .= '|'. $_GET['SETTLED'];
    }
    if(isset($_GET['CONTACT_ID'])){
      $authCodeConfirm .= '|'. $_GET['CONTACT_ID'];
    }
    if(isset($_GET['INCIDENT_ID'])){
      $authCodeConfirm .= '|'. $_GET['INCIDENT_ID'];
    }

    $authCodeConfirm = strtoupper(hash_hmac('sha256', $authCodeConfirm, $merchantPrivateKey));

    if ($authCodeConfirm == $authCode)
    {
      if ($orderId !== "")
      {
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        if ($orderNumber != $order->getMkOrderNumber())
          Mage::throwException($this->__('Different ordernumber in authcode calculation and get parameter'));
        if (($order->getStatus() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) || ($order->getStatus() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW))
        {
	    	if ($returnCode == 0)
	    	{
	    		if ($settled == 1) 
				{
					$order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
					$order->setMkSettled(1);
					$comment = $this->__("Payment authorized and settled. Maksukaista order number - ") . $order->getMkOrderNumber();
				}
				else if ($settled == 0)
				{
					$order->setStatus(Mage_Sales_Model_Order::STATE_HOLDED);
					$order->setMkSettled(0);
					$comment = $this->__("Payment authorized. Settlement will be done when invoice is created. Maksukaista order number - ") . $order->getMkOrderNumber();
				}
				$order->addStatusToHistory($order->getStatus(), $comment); 
				$order->sendNewOrderEmail();
				$order->save();
				echo json_encode(array('result' => 'notify ok'));
	    	} 
	    	else if ($returnCode == 1)
	    	{
	    		$order->cancel();
	    		$order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
	    		$comment = $this->getReturnCodeComment($returnCode, $order->getMkOrderNumber(), $incidentId);
	    		$order->addStatusToHistory($order->getStatus(), $comment);
	    		$order->save();
	    		echo json_encode(array('result' => 'notify ok'));
	    	}
				
        } else if ($order->getStatus() != "") {
			echo json_encode(array('result' => 'notify not needed'));
        }
      }
    }
  }
  public function responseAction() 
  {
    $returnCode = $_GET['RETURN_CODE'];      
    $orderNumber = $_GET['ORDER_NUMBER'];
    $authCode = $_GET['AUTHCODE'];   
    $incidentId = isset($_GET['INCIDENT_ID']) ? $_GET['INCIDENT_ID'] : '';
    $settled =  isset($_GET['SETTLED']) ? $_GET['SETTLED'] : '';
    $orderId = isset($_GET['id']) ? $_GET['id'] : '';
    
    $merchantPrivateKey =  Mage::getStoreConfig('payment/maksukaista/merchant_private_key');

    $authCodeConfirm = $returnCode.'|'.$orderNumber;

    if(isset($_GET['SETTLED'])){
      $authCodeConfirm .= '|'. $_GET['SETTLED'];
    }
    if(isset($_GET['CONTACT_ID'])){
      $authCodeConfirm .= '|'. $_GET['CONTACT_ID'];
    }
    if(isset($_GET['INCIDENT_ID'])){
      $authCodeConfirm .= '|'. $_GET['INCIDENT_ID'];
    }
    
    $authCodeConfirm = strtoupper(hash_hmac('sha256', $authCodeConfirm, $merchantPrivateKey));
    if (isset($orderId) && $orderId !== "")
    {
    	$order = Mage::getModel('sales/order');
	    $order->loadByIncrementId($orderId);

	    if ($orderNumber != $order->getMkOrderNumber())
			Mage::throwException($this->__('User sessions last order number does not match received order number'));

	    if ($order->getStatus() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) 
	    {
	      if ($authCodeConfirm == $authCode)
	      {
	        switch ($returnCode) {
	          case ReturnCodes::Success:
	            $this->success($order, $settled);
	            break;
	          case ReturnCodes::Failed:
	            $this->cancel($order, $returnCode, $incidentId);            
	            break;
	          case ReturnCodes::BankError:
	            $this->hold($order, $incidentId); 
	            break;
	          case ReturnCodes::Maintenance:
	            $this->cancel($order, $returnCode, $incidentId);
	            break;
	          default:
	            $this->cancel($order, $returnCode, $incidentId);         
	            break;
	        }
	      }
	      else     
	        Mage::throwException('Response MAC code not matching.');
	    }
	    else if ($order->getStatus() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW || $order->getStatus() == Mage_Sales_Model_Order::STATE_CANCELED)
	    {
	      Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
	    }
	    else if (($order->getStatus() == Mage_Sales_Model_Order::STATE_PROCESSING) || ($order->getStatus() == Mage_Sales_Model_Order::STATE_HOLDED))
	    {
	      Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));
	    }
    }
    
  }
  
  private function cancel($order, $returnCode, $incidentId = '') 
  {
    if (!$order->getId())
      Mage::throwException($this->__('No order id found.'));

    $order->cancel();
    $order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);

    $comment = $this->getReturnCodeComment($returnCode, $order->getMkOrderNumber(), $incidentId);
    $order->addStatusToHistory($order->getStatus(), $comment);
    $order->save();

    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
  }

  private function success($order, $settled)
  {
    if (!$order->getId())
      Mage::throwException($this->__('No order id found.'));

    $order->setMkSettled($settled)->save();

    if ($settled == 1)
    {
      $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
      $this->invoice($order);
      $comment = $this->__("Payment authorized and settled. Maksukaista order number - ") . $order->getMkOrderNumber();
    }
    else if($settled == 0)
    {
      $order->setStatus(Mage_Sales_Model_Order::STATE_HOLDED);
      $comment = $this->__("Payment authorized. Settlement will be done when invoice is created. Maksukaista order number - ") . $order->getMkOrderNumber();
    }
    else
      Mage::throwException($this->__("Invalid value of 'settled'"));     
    
    $order->addStatusToHistory($order->getStatus(), $comment);

    try
    {
      $order->sendNewOrderEmail();
    }
    catch(Exception $e)
    {
    }

    $order->save();

    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));    
  }

  private function hold($order, $incidentId)
  {
    if (!$order->getId())
      Mage::throwException($this->__('No order id found.'));

    $order->setStatus(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
    $order->addStatusToHistory($order->getStatus(), sprintf($this->__("Maksukaista - error in payment. Refresh payment status manually in merchant panel - order number %s incident id - %s") , $order->getMkOrderNumber() , $incident_id));
    $order->save();

    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
  }

  private function invoice($order)
  {
    if (!$order->canInvoice())
      Mage::throwException($this->__("Could not create invoice."));

    $invoice = $order->prepareInvoice();

    $invoice->register();
    $invoice->capture();

    $transaction = Mage::getModel('core/resource_transaction')
      ->addObject($invoice)
      ->addObject($invoice->getOrder());

    $transaction->save();

    $order->setMkSettled(1);
    $order->save();
  }

  private function getReturnCodeComment($returnCode, $orderId, $incidentId)
  {
    switch ($returnCode) 
    {
      case ReturnCodes::Failed:
        $comment = sprintf($this->__("Maksukaista - payment cancelled - order number - %s incident id - %s"), $orderId,  $incidentId);
        break;       
      case ReturnCodes::Maintenance:
        $comment = sprintf($this->__("Maksukaista - payment with order number %s not done, reason being a maintenance break in our service"), $orderId,  $incidentId); 
        break;
      default:
        $comment = $this->__("Maksukaista - unknown return code - ") . $returnCode;
        break;
    }
    return $comment;
  }
}
