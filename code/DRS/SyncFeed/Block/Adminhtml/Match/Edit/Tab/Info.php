<?php
namespace DRS\SyncFeed\Block\Adminhtml\Match\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Action;
use DRS\SyncFeed\Model\Config\Source\Categorylist;

class Info extends Generic implements TabInterface
{
	protected $_wysiwygConfig;

	protected $_fileUploaderFactory;

	protected $_sectionCollection;

	protected $_categoryList;

	public function __construct(
		Context $context,
		Registry $registry,
		FormFactory $formFactory,
		Categorylist $categoryList,
		array $data = []
		)
	{
		parent::__construct($context, $registry, $formFactory, $data);
		$this->_categoryList = $categoryList;
	}

	protected function _prepareForm()
	{
		$model = $this->_coreRegistry->registry('match_category');
		
		$form = $this->_formFactory->create(
			[
				'data' =>
				[
					'id' => 'edit_form', 
					'enctype'=>'multipart/form-data',
					'action' => $this->getData('action'), 
					'method' => 'post'
				]
			]
		);
		$form->setHtmlIdPrefix('match_');
		$form->setFieldNameSuffix('match');

		$fieldset = $form->addFieldset(
			'base_fieldset',
			['legend'=>__('General')]
		);

		if($model->getMatchId()){
			$fieldset->addField(
				'match_id',
				'hidden',
				['name'=>'match_id']
			);
		}

		$fieldset->addField(
			'category_feed',
			'textarea',
			[
				'name' => 'category_feed',
				'label' => __('Feed Category'),
				'required' => true
			]
		);

		// Get category array 		
		$category_list = $this->_categoryList->toOptionArray();

		$fieldset->addField( 
			'category_match', 
			'multiselect', 
			[ 
				'name' => 'category_match[]', 
				'label' => __('Categories'), 
				'required' => TRUE, 
				'values' => $category_list
 			] 
		);	
		$data = $model->getData();
		$form->setValues($data);
		$this->setForm($form);
		
		
		
		return parent::_prepareForm();	
	}

	public function getTabLabel()
	{
		return __('Match Info');
	}

	public function getTabTitle()
	{
		return __('Match Info');
	}

	public function canShowTab()
	{
		return true;
	}

	public function isHidden()
	{
		return false;
	}
}
?>