<?php
namespace DRS\SyncFeed\Model\ResourceModel\CategoryMatch;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_cachetable = 'drs_syncfeed_cache';
	protected $_idFieldName = 'match_id';
	protected $_eventPrefix = 'drs_syncfeed_categorymatch_collection';
	protected $_eventObject = 'categorymatch_collection';
	//protected $_resourceConnection;
	//protected $_connection;
	/**
	 * Define resource model
	 *
	 * @return void
	 */
	//protected function _construct(\Magento\Framework\App\ResourceConnection $resourceConnection)
	public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
	)
	{
		//$this->_resourceConnection = $resourceConnection;
		$this->_init('DRS\SyncFeed\Model\CategoryMatch', 'DRS\SyncFeed\Model\ResourceModel\CategoryMatch');

        parent::__construct(
            $entityFactory, $logger, $fetchStrategy, $eventManager, $connection,
            $resource
        );
        $this->storeManager = $storeManager;
	}

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->columns(
                array('count' => new \Zend_Db_Expr(
                    '(SELECT count(*) 
                        FROM 
                            '.$this->getTable($this->_cachetable).' a 
                        WHERE 
                            a.category = main_table.category_feed and a.item_status=1)'))
            )->order('count DESC');/*
            ->joinLeft(
                ['catalog_category' => $this->getTable('catalog_category_entity_varchar')],
                'main_table.category_match = catalog_category.entity_id',
                ['value']
            )->where('catalog_category.attribute_id = 45 and store_id = 1');*/
    }
 
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(\Zend_Db_Select::GROUP);
        return $countSelect;
    }

    protected function _toOptionArray($valueField = 'match_id', $labelField = 'category_feed', $additional = [])
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }

}
