<?php
namespace DRS\SyncFeed\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Match extends Container
{
	protected function _construct()
	{
		$this->_controller = 'adminhtml_match';
		$this->_blockGroup = 'DRS_SyncFeed';
		$this->_headerText = __('Match Category');
		$this->_addButtonLabel = __('Add Match Category');
		parent::_construct();
	}
}
?>