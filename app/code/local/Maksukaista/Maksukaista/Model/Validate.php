<?php
class Maksukaista_Maksukaista_Model_Validate extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $bankPayments = $this->getData('groups/maksukaista/fields/bank_payments/value');
        $ccPayments = $this->getData('groups/maksukaista/fields/creditcards_payments/value');
        $invoicePayments = $this->getData('groups/maksukaista/fields/invoice_payments/value');
        $arvatoPayments = $this->getData('groups/maksukaista/fields/arvato_payments/value');
        $laskuyritykselle = $this->getData('groups/maksukaista/fields/laskuyritykselle/value');
		$order_prefix = $this->getData('groups/maksukaista/fields/orderid_prefix/value');

        $errors = array();
        if (($bankPayments == 0) && ($ccPayments == 0) && ($invoicePayments == 0) && ($arvatoPayments == 0) && ($laskuyritykselle == 0))
        {
        	array_push($errors, Mage::helper('maksukaista')->__("You have to select at least one payment method."));
        }

 		if (!preg_match("/^[a-zA-Z0-9_-]*$/", $order_prefix))
 			array_push($errors, Mage::helper('maksukaista')->__("Order prefix can contain only letters from a-z, numbers, - and _."));

		if (count($errors) > 0)
			Mage::throwException(Mage::helper('maksukaista')->__("Saving config failed!") . " " . implode(" ", $errors)); 

        return parent::save();
    }
}
?>