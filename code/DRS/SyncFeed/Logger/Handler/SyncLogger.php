<?php
 
namespace DRS\SyncFeed\Logger\Handler;
 
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
 
class SyncLogger extends Base
{
 
    /**
     * @var string
     */
    protected $fileName = '/var/log/syncfeed.log';
 
    /**
     * @var
     */
    protected $loggerType = Logger::DEBUG;
 
}