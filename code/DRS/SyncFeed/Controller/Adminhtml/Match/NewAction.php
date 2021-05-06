<?php
namespace DRS\SyncFeed\Controller\Adminhtml\Match;

use DRS\SyncFeed\Controller\Adminhtml\Match;

class NewAction extends Match
{
	public function execute()
	{
		$this->_forward('edit');
	}
}
?>