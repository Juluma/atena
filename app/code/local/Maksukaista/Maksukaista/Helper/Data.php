<?php

class Maksukaista_Maksukaista_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected static $api_url = 'https://www.paybyway.com/pbwapi/';

	public function getApiUrl()
	{
		return static::$api_url;
	}

	public function getAuthUrl()
	{
		return static::$api_url . 'auth_payment';
	}

	public function getCaptureUrl()
	{
		return static::$api_url . 'capture';
	}

	public function getDPMUrl()
	{
		return static::$api_url . 'get_merchant_payment_methods';
	}

	public function calcAuthCode($input)
	{
		$private_key = Mage::getStoreConfig('payment/maksukaista/merchant_private_key');
		return strtoupper(hash_hmac('sha256', $input, $private_key));
	}
}