<?php
namespace DRS\SyncFeed\Model;
  
class CategoryMatch extends \Magento\Framework\Model\AbstractModel
{
    protected $_storeManagerInterface;
    /**
     * CMS page cache tag.
     */
    //const CACHE_TAG = 'DRS_syncfeed_categorymatch';
 
    /**
     * @var string
     */
    //protected $_cacheTag = 'DRS_syncfeed_categorymatch';
 
    /**
     * Prefix of model events names.
     *
     * @var string
     */
  //  protected $_eventPrefix = 'DRS_syncfeed_categorymatch';
 
    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('DRS\SyncFeed\Model\ResourceModel\CategoryMatch');
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

}