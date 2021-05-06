<?php
namespace DRS\SyncFeed\Controller\Adminhtml\Match;

use DRS\SyncFeed\Controller\Adminhtml\Match;

class Edit extends Match
{
	public function execute()
	{
		$match_id = $this->getRequest()->getParam('match_id');
		$model = $this->_categorymatchFactory->create();

		if($match_id) {
			$model->load($match_id);
			if(!$model->getMatchId()) {
				$this->messageManager->addError(__('This match no longer exists.'));
				$this->_redirect('*/*/');
				return;
			}
		}

		//Restore preciously entered form data from session
		$data = $this->_session->getNewsData(true);
		if(!empty($data)) {
			$model->setData($data);
		}
		$this->_coreRegistry->register('match_category', $model);

		$resultPage = $this->_resultPageFactory->create();
		$resultPage->setActiveMenu('DRS_SyncFeed::Match_Category');
		$resultPage->getConfig()->getTitle()->prepend(__('Match Category'));

		return $resultPage;
	}
}
?>