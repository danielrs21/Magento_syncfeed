<?php
namespace DRS\SyncFeed\Block\Adminhtml\Match;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class Edit extends Container
{
	protected $_coreRegistry = null;

	public function __construct(
		Context $context,
		Registry $registry,
		array $data = []
		)
	{
		$this->_coreRegistry = $registry;
		parent::__construct($context, $data);
	}

	protected function _construct()
	{
		$this->_objectId = 'match_id';
		$this->_controller = 'adminhtml_match';
		$this->_blockGroup = 'DRS_SyncFeed';

		parent::_construct();

		$this->buttonList->update('save', 'label', __('Save'));
		$this->buttonList->add(
			'saveandcontinue',
			[
				'label'=>__('Save and Continue Edit'),
				'class'=>'save',
				'data_attribute'=>[
					'mage-init'=>[
						'button'=>[
							'event'=>'saveAndContinueEdit',
							'target'=>'#edit_form'
						]
					]
				]
			]
		);
		$this->buttonList->update('delete','label',__('Delete'));
	}

	public function getHeaderText()
	{
		$reviewRegistry = $this->_coreRegistry->registry('match_category');
		if ($reviewRegistry->getReviewId()) {
			$title = $this->escapeHtml($reviewRegistry->getTitle());
			return __("Edit Match '%1'", $title);
		} else {
			return __('Add Match');
		}
	}

	protected function _prepareLayout()
	{
		return parent::_prepareLayout();
	}
}
?>