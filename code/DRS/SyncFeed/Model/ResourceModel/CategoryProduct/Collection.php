<?php
namespace DRS\SyncFeed\Model\ResourceModel\CategoryProduct;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'entity_id';
	protected $_eventPrefix = 'drs_syncfeed_categoryproduct_collection';
	protected $_eventObject = 'categoryproduct_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('DRS\SyncFeed\Model\CategoryProduct', 'DRS\SyncFeed\Model\ResourceModel\CategoryProduct');
	}

}