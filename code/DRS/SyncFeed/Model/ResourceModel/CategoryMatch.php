<?php
namespace DRS\SyncFeed\Model\ResourceModel;
 
/**
 * Category Match mysql resource.
 */
class CategoryMatch extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string
     */
 //   protected $_idFieldName = 'match_id';
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
 
    /**
     * Construct.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime       $date
     * @param string|null                                       $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $resourcePrefix = null
    ) 
    {
        $this->_date = $date;       
        parent::__construct($context);
    }
 
    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('drs_syncfeed_categorymatch', 'match_id');
    }
}