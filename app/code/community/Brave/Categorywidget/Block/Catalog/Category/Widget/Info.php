<?php

class Brave_Categorywidget_Block_Catalog_Category_Widget_Info
    extends Brave_Categorywidget_Block_Catalog_Category_Info
    implements Mage_Widget_Block_Interface
{
    protected function _prepareCategory()
    {
        $this->_validateCategory();

        $category = $this->_getData('category');
        if (false !== strpos($category, '/')) {
            $category = explode('/', $category);
            $this->setData('category', (int)end($category));
        }
        return parent::_prepareCategory();
    }
}