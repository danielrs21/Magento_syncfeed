<?php
namespace DRS\SyncFeed\Controller\Adminhtml\Match;

use DRS\SyncFeed\Controller\Adminhtml\Match;
 
class Grid extends Match
{
   /**
     * @return void
     */
   public function execute()
   {
      return $this->_resultPageFactory->create();
   }

}
?>