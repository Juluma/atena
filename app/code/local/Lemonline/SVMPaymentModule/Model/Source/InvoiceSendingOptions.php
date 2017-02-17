<?php
/**
 * 
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lemonline.
 *
 * @category   Lemonline
 * @copyright  Copyright (c) 2009 Lemonline (http://www.lemonline.fi)
 * @package Model
 *
 */
class Lemonline_SVMPaymentModule_Model_Source_InvoiceSendingOptions
{
	/**
	 * Return invoice email sending options
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array(
				"value" => "0",
				"label" => Mage::helper("svm")->__('When payment is successfully confirmed')
			),
			array(
				"value" => "1",
				"label" => Mage::helper("svm")->__('Never')
			),
		);
	}

}
