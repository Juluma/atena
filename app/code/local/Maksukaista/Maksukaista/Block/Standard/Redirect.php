<?php 
class Maksukaista_Maksukaista_Block_Standard_Redirect extends Mage_Core_Block_Abstract
{
	protected function _toHtml()
	{

		
		$api = Mage::getModel('maksukaista/standard');
		$helper = Mage::helper('maksukaista');
		$order = Mage::getModel('sales/order');
		$checkout_session = Mage::getSingleton('checkout/session');
		$orderId = $checkout_session->getLastRealOrderId();
		$order->loadByIncrementId($orderId);
		$apikey = Mage::getStoreConfig('payment/maksukaista/api_key');
		$embedded = Mage::getStoreConfig('payment/maksukaista/embedded');
		$isDPM = Mage::getStoreConfig('payment/maksukaista/dynamic_payment_methods');
		$send_items = Mage::getStoreConfig('payment/maksukaista/send_items');
		$bank_payments = Mage::getStoreConfig('payment/maksukaista/bank_payments');
		$creditcards_payments = Mage::getStoreConfig('payment/maksukaista/creditcards_payments');
		$invoice_payments = Mage::getStoreConfig('payment/maksukaista/invoice_payments');
		$arvato_payments = Mage::getStoreConfig('payment/maksukaista/arvato_payments');
		$laskuyritykselle = Mage::getStoreConfig('payment/maksukaista/laskuyritykselle');

		$auth_url = $helper->getAuthUrl();
		$token_url = $helper->getApiUrl() . "token/";

		$shipping_tax_percent = ($order->getShippingInclTax() == 0) ? 0 : round(100 * ($order->getShippingTaxAmount() / $order->getShippingInclTax()));
		$products = array();
		$product_amount = 0;
		if ($send_items == 1)
		{
			foreach ($order->getAllVisibleItems() as $item) 
			{
				$product = array(
					'title' => $item->getName(),
					'id' => $item->getSku(),
					'count' => (int)$item->getQtyOrdered(),
					'pretax_price' => (int)(round($item->getPrice()*100)),
					'price' => (int)(round($item->getPriceInclTax()*100)),
					'tax' => (int)$item->getTaxPercent(),
					'type' => 1
				);
				$product_amount += $product['price'] * $product['count'];
				array_push($products, $product);
			}

			if ($order->getDiscountAmount() != 0)
			{
				$product = array(
					'title' => $order->getDiscountDescription(),
					'id' => "discount",
					'count' => 1,
					'pretax_price' => (int)(round($order->getDiscountAmount()*100)),
					'price' => (int)(round($order->getDiscountAmount()*100)),
					'tax' => 0,
					'type' => 4
				);
				$product_amount += $product['price'];
				array_push($products, $product);

			}
			$product = array(
				'title' => $order->getShippingDescription(),
				'id' => $order->getShippingMethod(),
				'count' => 1,
				'pretax_price' => (int)(round($order->getShippingAmount()*100)),
				'price' => (int)(round($order->getShippingInclTax()*100)),
				'tax' => (int)$shipping_tax_percent,
				'type' => 2
			);
			$product_amount += $product['price'];
			array_push($products, $product);
		}


		$currency = $order->getBaseCurrencyCode();
		if($currency != 'EUR'){
			Mage::throwException("Invalid currency, only EUR is accepted");
		}
		$version = 'w3';
		$amount = (int)(round($order->getBaseGrandTotal()*100));
		$returnAddress = Mage::getUrl('maksukaista/payment/response', array( '_nosid' => true, '_secure' => true));
		$returnAddress .= "?id=" . $orderId;
		$cancelAddress = $returnAddress;
		$notifyAddress = Mage::getUrl('maksukaista/payment/notify', array( '_nosid' => true, '_secure' => true));
		$notifyAddress .= "?id=" . $orderId;
		$orderId = $order->getMkOrderNumber();
		
		$selected = array();
		if ($embedded == 1)
		{
			$paymentMethod = $checkout_session->getPaymentMethod();
			array_push($selected,$paymentMethod);
		}
		else
		{
			if ($bank_payments == 1)
				array_push($selected,"banks");
			if ($creditcards_payments == 1)
				array_push($selected,"creditcards");
			if ($invoice_payments == 1)
				array_push($selected,"creditinvoices");
			if ($laskuyritykselle == 1)
				array_push($selected,"laskuyritykselle");
			if ($arvato_payments == 1)
				array_push($selected,"arvato");
		}

		$locale = Mage::app()->getLocale()->getLocaleCode();
		if ($locale == 'fi_FI') {
			$lang = 'fi';	
		}
		else {
			$lang = 'en';
		}

		$customer_data = $order->getBillingAddress();
		$customer_info = array(
			'firstname' => $customer_data->getFirstname(),
			'lastname' => $customer_data->getLastname(), 
			'email' => $customer_data->getEmail(),
			'address_street' => $customer_data->getStreet1(),
			'address_city' => $customer_data->getCity(),
			'address_zip' => $customer_data->getPostcode()
		);

		$authCodeInput = $apikey . "|" . $orderId;

		$payment_method = array(
			'type' => 'e-payment',
			'return_url' => $returnAddress,
			"notify_url" => $notifyAddress,
			"lang" => $lang,
		);
		if (count($selected) > 0)
			$payment_method["selected"] = $selected;		

		$authCode = $helper->calcAuthCode($authCodeInput);
		$data = array(
			'version' => $version,
			'api_key' => $apikey,
			'order_number' => $orderId,
			'amount' => $amount,
			'currency' => $currency,
			'payment_method' => $payment_method,
			'authcode' => $authCode,
			'customer' => $customer_info

		);
		if ((count($products) > 0) && ($product_amount == $amount))
			$data['products'] = $products;

		$send_maksukaista_receipt = Mage::getStoreConfig('payment/maksukaista/send_receipt');
		if ($send_maksukaista_receipt == 1)
			$data["email"] = $customer_data->getEmail();

		$ctype = array('Content-Type: application/json', 'Content-Length: ' . strlen(json_encode($data)));
		$auth_response = json_decode($api->curl($auth_url, $ctype, json_encode($data)));

		if (isset($auth_response->result) && $auth_response->result === 0)
		{
			$payurl = $token_url . $auth_response->token;
		    $order->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		    $order->addStatusToHistory($order->getStatus(), $this->__("Payment forwarded to Maksukaista gateway with order number ") . $orderId);
		    $order->save();
			$html = "<script>window.location.href = '$payurl'</script>";
		}
		else if (isset($auth_response->result) && $auth_response->result === 1) {
			$order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
		    $order->addStatusToHistory($order->getStatus(), $this->__("Validation error in Maksukaista with order number: ") . $orderId . ". " . $this->__("Please check your api key and private key."));
		    $order->save();
			$html = $this->__("Error occurred while trying to register payment. Please contact the merchant with order number: ") . $orderId;
		}
		else if (isset($auth_response->result) && $auth_response->result === 2) {
			$order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
		    $order->addStatusToHistory($order->getStatus(), $this->__("Duplicate order id ") . $orderId);
		    $order->save();
			$html = $this->__("Error occurred while trying to register payment. Please contact the merchant with order number: ") . $orderId;
		}
		else
		{
			$order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
		    $order->addStatusToHistory($order->getStatus(), $this->__("Curl failed when trying to get payment token"));
		    $order->save();
			$html = $this->__("Error occurred while trying to register payment. Please contact the merchant with order number: ") . $orderId;
		}
		return $html;
	}
}
