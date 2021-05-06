<?php
namespace DRS\SyncFeed\Controller\Adminhtml\Match;

use DRS\SyncFeed\Controller\Adminhtml\Match;

class Delete extends Match
{
	public function execute()
	{
		$match_id = (int) $this->getRequest()->getParam('match_id');
		if ($match_id) {
			$matchModel = $this->_categorymatchFactory->create();
			$matchModel->load($match_id);

			//Check this new exists or not
			if(!$matchModel->getMatchId()) {
				$this->messageManager->addErrorMessage(__('This match no longer exists.'));
			} else {
				try {
					//Delete category
					$matchModel->delete();
					$this->messageManager->addSuccessMessage(__('The match has been deleted.'));

					//Redirect to grid page
					$this->_redirect('*/*/');
					return;
				} catch (\Exception $e) {
					$this->messageManager->addErrorMessage($e->getMessage());
					$this->_redirect('*/*/edit', ['id'=>$matchModel->getMatchId()]);
				}
			}
		} else {
			$this->messageManager->addErrorMessage(__('An error ocurred.'));
			return;
		}
	}
}
?>