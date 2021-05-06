<?php

namespace DRS\SyncFeed\Model\Config\Source;

//use Magento\Framework\Option\ArrayInterface;
//use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductCount // extends AbstractElement implements ArrayInterface
{
    protected $_productCollectionFactory;

    public function __construct(
        CollectionFactory $productCollectionFactory
    )
    {
        $this->_productCollectionFactory = $productCollectionFactory;
    }

    public function getCount($category_origin){

        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToFilter( 'category_origin', $category_origin );
        $collection->addAttributeToSelect(array('category_ids'));

        return (int) $collection->getSize();

    }
 
}