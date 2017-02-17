<?php
class Maksukaista_Maksukaista_Model_Standard extends Mage_Payment_Model_Method_Abstract 
{
  protected $_code = 'maksukaista';
  
  protected $_canUseForMultishipping  = false;
  protected $_isGateway = true;
  protected $_canAuthorize = true;
  protected $_canCapture = true;
  protected $_canCapturePartial = false;
  protected $_canRefund = false;
  protected $_canVoid = false;
  protected $_canUseInternal = false;
  protected $_canUseCheckout = true;

  protected $_formBlockType = 'maksukaista/standard_form';
  protected $_redirectBlockType = 'maksukaista/standard_redirect';

  public function assignData($data)
  {
    Mage::getSingleton('checkout/session')->setPaymentMethod($data->getPaymentMethod());
  }

  public function getOrderPlaceRedirectUrl() 
  {
    return Mage::getUrl('maksukaista/payment/redirect', array('_secure' => true));
  }

  public function authorize(Varien_Object $payment, $amount)
  {
    return $this;
  }

  public function capture(Varien_Object $payment, $amount)
  { 
    $order = $payment->getOrder();
    $helper = Mage::helper('maksukaista');
    $private_key = Mage::getStoreConfig('payment/maksukaista/merchant_private_key');
    $api_key = Mage::getStoreConfig('payment/maksukaista/api_key');
    $order_number = $order->getMkOrderNumber();
    $authcode = $helper->calcAuthCode($api_key . "|" . $order_number);
    if ($order->getMkSettled() == 1)
      return $this;

    $url = $helper->getCaptureUrl();

    $ctype = 'application/json';
    $posts = array(
		'version' => 'w3',
		'api_key' => $api_key,
		'order_number' => $order_number,
		'authcode' => $authcode
    );
    $settlement = json_decode($this->curl($url, $ctype, json_encode($posts)));

    if (!isset($settlement->result))
      Mage::throwException(Mage::helper('maksukaista')->__("Error in executing Maksukaista API settlement request"));
    switch ($settlement->result) {
      case 0:
        $order->setMkSettled(1)->save();
        return $this;       
      case 1:	
        Mage::throwException(Mage::helper('maksukaista')->__('Validation Error.'));      
      case 2:
	    Mage::throwException(Mage::helper('maksukaista')->__('Payment cannot be captured. Authorization was cancelled or card cannot be billed.'));
      case 3:
		Mage::throwException(Mage::helper('maksukaista')->__('Transaction for given order_number was not found.'));
      default:
        Mage::throwException(Mage::helper('maksukaista')->__('Unknown return value on settlement'));
    }
  
  }

  	public function curl($url, $ctype, $posts)
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_HEADER, 0); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $ctype);

	    curl_setopt($ch, CURLOPT_POSTFIELDS, $posts);
	    $curl_response = curl_exec ($ch);
	    curl_close ($ch);
	    return $curl_response;
	}
}
?>
