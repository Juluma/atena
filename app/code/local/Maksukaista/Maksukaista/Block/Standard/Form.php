<?php 
class Maksukaista_Maksukaista_Block_Standard_Form extends Mage_Payment_Block_Form
{
	protected function _toHtml()
	{
		$quote = Mage::getModel('checkout/session')->getQuote();
		$quoteData = $quote->getData();
		$amount = (int)(round($quoteData['grand_total']*100));
		$api = Mage::getModel('maksukaista/standard');
		$helper = Mage::helper('maksukaista');
		$isEmbedded = Mage::getStoreConfig('payment/maksukaista/embedded');
		$isDPM = Mage::getStoreConfig('payment/maksukaista/dynamic_payment_methods');
		$bankPayments = Mage::getStoreConfig('payment/maksukaista/bank_payments');
		$ccPayments = Mage::getStoreConfig('payment/maksukaista/creditcards_payments');
		$invoicePayments = Mage::getStoreConfig('payment/maksukaista/invoice_payments');
		$laskuyritykselle = Mage::getStoreConfig('payment/maksukaista/laskuyritykselle');
		$arvatoPayments = Mage::getStoreConfig('payment/maksukaista/arvato_payments');
		$apikey = Mage::getStoreConfig('payment/maksukaista/api_key');

		$_code=$this->getMethodCode();

		$html = '<ul class="form-list" id="payment_form_' . $_code . '" style="display:none;">';
		$error = "";

		if ($isEmbedded == 1)
		{
			$html .= '<li><label for="' . $_code . '_payment_method" class="required"><em>*</em></label>';
			$html .= '<select class="required-entry" id="'.$_code . '_payment_method" name="payment[payment_method]" />';
			if ($isDPM == 1)
			{
				$post_arr = array(
					'authcode' => $helper->calcAuthCode($apikey),
					'api_key' => $apikey
				);
				$ctype = array('Content-Type: application/json', 'Content-Length: ' . strlen(json_encode($post_arr)));
				$json = $api->curl($helper->getDPMUrl(), $ctype, json_encode($post_arr));
				$response = json_decode($json);
				if ($response->result === 0)
				{
					if ($ccPayments == 1)
					{
						foreach($response->payment_methods->creditcards as $name => $creditcard)
						{
							$html .= '<option value="creditcards">' . $creditcard->name . '</option>';
						}
					}
					
					if ($bankPayments == 1)
					{
						foreach($response->payment_methods->banks as $name => $bank)
						{
							$html .= '<option value="' . $name . '">' . $bank->name . '</option>';
						}
					}
					if ($invoicePayments == 1 || ($arvatoPayments == 1 || $laskuyritykselle == 1))
					{
						foreach($response->payment_methods->creditinvoices as $name => $creditinvoice)
						{
							if (($name == 'laskuyritykselle') || ($name == 'arvato'))
							{
								if (($laskuyritykselle == 1 && $name == 'laskuyritykselle') || ($arvatoPayments == 1 && $name == 'arvato'))
									$html .= '<option value="' . $name . '">' . $creditinvoice->name . '</option>';
							}
							else if ($invoicePayments == 1)
							{
								if ($amount >= $creditinvoice->min_amount && $amount <= $creditinvoice->max_amount)
									$html .= '<option value="' . $name . '">' . $creditinvoice->name . '</option>';
							}
						}
					} 
					
				} else {
					$error = "Could not get payment methods";
				}

			}

			if ($isDPM == 0 || $error != "")
			{
				if ($ccPayments == 1)
					$html .= '<option value="creditcards">Korttimaksu (Visa ja MasterCard)</option>';
				if ($bankPayments == 1)
				{
				  $html .= '<option value="aktia">Aktia</option>
							<option value="danskebank">Danskebank</option>
							<option value="handelsbanken">Handelsbanken</option>
							<option value="nordea">Nordea</option>
							<option value="osuuspankki">Osuuspankki</option>
							<option value="paikallisosuuspankki">Paikallisosuuspankki</option>
							<option value="spankki">S-pankki</option>
							<option value="saastopankki">Säästöpankki</option>
							<option value="alandsbanken">Ålandsbanken</option>';
				}
				if ($arvatoPayments == 1)
					$html .= '<option value="arvato">Maksukaista Lasku (Arvato)</option>';
				if ($invoicePayments == 1)
				{
					if ($amount >= 2000 && $amount <= 200000)
						$html .= '<option value="joustoraha">Jousto</option>';
					if ($amount >= 501 && $amount <= 200000)
						$html .= '<option value="everyday">Everyday</option>';
					if ($amount >= 1000)
						$html .= '<option value="euroloan">Euroloan</option>';
				}
				if ($laskuyritykselle == 1)
				{
					$html .= '<option value="laskuyritykselle">Laskuyritykselle</option>';
				}
				$html .= '</select>';
			}   
			
		} 
		else 
			$html .= '<li style="display:none;"><label for="' . $_code . '_payment_method"></label>';


		$html .= '</li>
		        </ul>';

		return $html;
	}
}
?>