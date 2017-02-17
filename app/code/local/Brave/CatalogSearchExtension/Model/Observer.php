<?php
class Brave_CatalogSearchExtension_Model_Observer {

	public function ExtendedSearch (Varien_Event_Observer $observer) {
		$session = Mage::getSingleton('core/session');
		$session->unsAuthorSearchResults();
		$session->unsPageSearchResults();

		$qParams = Mage::app()->getRequest()->getParams();
		$search = $qParams['q'];

		$categories = Mage::getModel('catalog/category')
        ->getCollection()
        ->addAttributeToSelect('*')
        ->addAttributeToFilter('name', array('like' => '%'.$search.'%'))
        ->addAttributeToFilter('parent_id', array('eq' => 20))
        ->addIsActiveFilter();

        $authorResults = [];
        $authors = [];
        $i = 0;
		foreach($categories as $category) {
			$authorResults[$i] = $category->getData();
			$authors[] = $category->getId();
			$i++;
		}

		// Get also authors who might have written this searchable booky
		$products = Mage::getResourceModel('catalog/product_collection')
        ->addAttributeToSelect('*')
        ->addAttributeToFilter('name', array('like' => '%'.$search.'%'));

        foreach ($products as $product) {
        	$cats = $product->getCategoryIds();
        	foreach ($cats as $cat_id) {
				$category = Mage::getModel('catalog/category')->load($cat_id);
		        if ($category->getParentId() == 20 && !in_array($category->getId(), $authors)) {
					$authorResults[$i] = $category->getData();
					$authors[] = $category->getId();
					$i++;
		        }
        	}
        }

		$session->setAuthorSearchResults($authorResults);

		$pages = Mage::getModel('cms/page')
		->getCollection()
		->addFieldToSelect('*')
		->addFieldToFilter('title', ['like' => '%'.$search.'%'])
		->addFieldToFilter('content', ['like' => '%'.$search.'%'])
		->addFieldToFilter('is_active', 1);

        $pageResults = [];
        $i = 0;
		foreach($pages as $page) {
			$pageResults[$i] = $page->getData();
			$i++;
		}
		$session->setPageSearchResults($pageResults);

	}

}
