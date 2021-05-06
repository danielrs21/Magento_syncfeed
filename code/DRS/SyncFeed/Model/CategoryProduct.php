<?php
namespace DRS\SyncFeed\Model;

class CategoryProduct extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'drs_syncfeed_categoryproduct';

	protected $_cacheTag = 'drs_syncfeed_categoryproduct';

	protected $_eventPrefix = 'drs_syncfeed_categoryproduct';

	protected function _construct()
	{
		$this->_init('DRS\SyncFeed\Model\ResourceModel\CategoryProduct');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}

}