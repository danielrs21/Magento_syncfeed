<?php
 
namespace DRS\SyncFeed\Logger\Handler;
 
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
 
class SyncLogQuery extends Base
{
 
    /**
     * @var string
     */
    protected $fileName = '/var/log/syncfeed_query.log';
 
    /**
     * @var
     */
    protected $loggerType = Logger::DEBUG;
 
}