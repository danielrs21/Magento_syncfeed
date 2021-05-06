<?php
namespace DRS\SyncFeed\Block\Adminhtml\Match\Edit;

use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

class Tabs extends WidgetTabs
{
	protected function _construct()
	{
		parent::_construct();
		$this->setId('match_edit_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(__('Match Information'));
	}

	protected function _beforeToHtml()
	{
		$this->addTab(
			'match_info',
			[
				'label'=>__('General'),
				'title'=>__('General'),
				'content'=>$this->getLayout()->createBlock(
					'DRS\SyncFeed\Block\Adminhtml\Match\Edit\Tab\Info'
				)->toHtml(),
				'active'=>true
			]
		);

		return parent::_beforeToHtml();
	}
}
?>