<?php
namespace DRS\SyncFeed\Controller\Adminhtml\Match;

use DRS\SyncFeed\Controller\Adminhtml\Match;

class Index extends Match
{
	public function execute()
	{
		if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }

        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('DRS_SyncFeed::Match_Category');
        $resultPage->getConfig()->getTitle()->prepend(__('Match Category'));
 
        return $resultPage;
	}
}
?>