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
class Lemonline_SVMPaymentModule_Model_Source_Languages
{
	/**
	 * Return Language options for the UI 
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array(
				"value" => "fi_FI",
				"label" => Mage::helper("svm")->__('Finnish')
			),
			array(
				"value" => "sv_SE",
				"label" => Mage::helper("svm")->__('Swedish')
			),
			array(
				"value" => "en_US",
				"label" => Mage::helper("svm")->__('English')
			),
			array(
				"value" => "ru_RU",
				"label" => Mage::helper("svm")->__('Russian')
			),
		);
	}

}
