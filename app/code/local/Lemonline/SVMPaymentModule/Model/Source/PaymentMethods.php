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
class Lemonline_SVMPaymentModule_Model_Source_PaymentMethods
{
	/**
	 * Return invoice email sending options
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array("value"=>1,"label"=>"Nordea"),
			array("value"=>2,"label"=>"Osuuspankki"),
			array("value"=>3,"label"=>"Sampo Pankki"),
			array("value"=>4,"label"=>"Tapiola"),
			array("value"=>5,"label"=>"Ålandsbanken"),
			array("value"=>6,"label"=>"Handelsbanken"),
			array("value"=>7,"label"=>"Säästöpankit, paikallisosuuspankit, Aktia, Nooa"),
			array("value"=>8,"label"=>"Luottokunta"),
			array("value"=>9,"label"=>"Paypal"),
			array("value"=>10,"label"=>"S-Pankki"),
			array("value"=>11,"label"=>"Klarna, Laskulla"),
			array("value"=>12,"label"=>"Klarna, Osamaksulla"),
			
		);

	}

}
