<?php
class Brave_PaymentMethodFilter_Model_Observer {

    public function filterpaymentmethod (Varien_Event_Observer $observer) {

        /* call get payment method */
        $method = $observer->getEvent()->getMethodInstance();
        $quote = $observer->getEvent()->getQuote();

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $roleId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            $role = Mage::getSingleton('customer/group')->load($roleId)->getData('customer_group_code');

            if ($role == 'Yritysasiakas') {
                if ($method->getCode() == 'purchaseorder') {
                    $result = $observer->getEvent()->getResult();
                    $result->isAvailable = true;
                    return;
                }
                else {
                    $result = $observer->getEvent()->getResult();
                    $result->isAvailable = false;
                    return;
                }
            }
        }

        if ($method->getCode() == 'purchaseorder') {
            $result = $observer->getEvent()->getResult();
            $result->isAvailable = false;
            return;
        }
    }
}
?>