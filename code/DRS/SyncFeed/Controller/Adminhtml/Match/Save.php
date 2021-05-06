<?php
namespace DRS\SyncFeed\Controller\Adminhtml\Match;

use DRS\SyncFeed\Controller\Adminhtml\Match;

class Save extends Match
{
	public function execute()
	{
		$post = $this->getRequest()->getPost();

		if($post) {
			$matchFactory = $this->_categorymatchFactory->create();
			$data = $this->getRequest()->getParam('match');
		}

		if(isset($data['match_id'])){
			$matchID = $data['match_id'];
			$matchFactory->load($matchID);
		}

		try {
			// Convert array to string comma separated
			$catIds = implode(",",$data['category_match']);
			$data['category_match'] = $catIds;

			$matchFactory->setData($data);

			// Actualizar los productos asociados al category origin
			$result = $this->_productStorage->assignCategoryProduct(
							explode(",",$data['category_match']),
							$data['category_feed']
						);

			//Save category
			$matchFactory->save();
			//Display succes message
			$this->messageManager->addSuccessMessage(__('The match has been saved.'));
			
			//Check if 'Save and Continue'
			if($this->getRequest()->getParam('back')) {
				$this->_redirect('*/*/edit', ['match_id'=>$matchFactory->getMatchId(), '_current'=>true]);
				return;
			}

			//Go to grid page
			$this->_redirect('*/*/');
			return;
			
		} catch (\Exception $e) {
			$this->messageManager->addErrorMessage($e->getMessage());
		}

		$this->_getSession()->setFormData($post);
		if(isset($matchID)){
			$this->_redirect('*/*/edit', ['match_id'=>$matchID]);
		} else {
			$this->_redirect('*/*/');
		}
		
	}
}
?>