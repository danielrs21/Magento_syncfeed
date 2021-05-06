<?php
namespace DRS\SyncFeed\Model\ResourceModel;


class CategoryProduct extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('catalog_category_product', 'entity_id');
	}
	
}