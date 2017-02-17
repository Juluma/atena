<?php
/**
 * 
 * NOTICE OF LICENSE
 *
 * This source file is released and licensed under a limited, non-exclusive and non-assignable commercial license by Lemonline. 
 *
 * @category   Lemonline
 * @package    Lemonline_SVMPaymentModule
 * @copyright  Copyright (c) 2012 Lemonline (http://www.lemonline.fi)
 * @license    http://www.lemonline.fi/licenses/lemonline-license-1.0.txt  Lemonline License
 */
class Lemonline_SVMPaymentModule_Block_Form extends Mage_Payment_Block_Form {

    /**
     * (non-PHPdoc)
     * @see app/code/core/Mage/Core/Block/Mage_Core_Block_Abstract#_construct()
     */
    protected function _construct() {
        $this->setTemplate('svm/form.phtml');
        parent::_construct();
    }

}
