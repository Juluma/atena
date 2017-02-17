<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Widget to display link to the product
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */

class Mage_Catalog_Block_Product_Widget_Link
    extends Mage_Catalog_Block_Widget_Link
{
    protected $_product = null;

    protected $_imagePath = null;

    /**
     * Initialize entity model
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_entityResource = Mage::getResourceSingleton('catalog/product');
    }

    public function getProduct()
    {
        if (!$this->_product && $this->_entityResource) {
            $idPath = explode('/', $this->_getData('id_path'));
            if (isset($idPath[1])) {
                $id = $idPath[1];
                if ($id) {
                    $this->_product = Mage::getModel('catalog/product')->load($id);
                }
            }
        }
        return $this->_product;
    }

    /**
     * Prepare anchor text using passed text as parameter.
     * If anchor text was not specified get entity name from DB.
     *
     * @return string
     */
    public function getAnchorText()
    {
        if (!$this->_anchorText && $this->_entityResource) {
            if ($this->_getData('anchor_text')) {
                $this->_anchorText = $this->_getData('anchor_text');
            }
        }

        return $this->_anchorText;
    }

}
