<?php

class Brave_Categorywidget_Block_Catalog_Category_Info extends Mage_Core_Block_Template
{
    protected $_category;

    protected function _beforeToHtml()
    {
        $this->_category = $this->_prepareCategory();
        return parent::_beforeToHtml();
    }

    protected function _prepareCategory()
    {
        $this->_validateCategory();
        return Mage::getModel('catalog/category')->load($this->_getData('category'));
    }

    protected function _validateCategory()
    {
        if (! $this->hasData('category')) {
            throw new Exception('Category must be set for info block');
        }
    }

    public function getCategoryName()
    {
        return $this->_category->getName();
    }

    public function getCategoryDescription()
    {
        return $this->_category->getDescription();
    }

    public function getCategoryImage()
    {
        return $this->_category->getImageUrl();
    }

    public function getCategoryUrl()
    {
        return $this->_category->getUrl();
    }
}