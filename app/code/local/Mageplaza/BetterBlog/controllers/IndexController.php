<?php

class Mageplaza_BetterBlog_IndexController extends Mage_Core_Controller_Front_Action{

    /**
     * Redirect to main page
     */
    public function indexAction()
    {
        $this->_redirect('*');
    }

    public function testAction()
    {
        Mage::getModel('mageplaza_betterblog/import')->aw();
    }

}