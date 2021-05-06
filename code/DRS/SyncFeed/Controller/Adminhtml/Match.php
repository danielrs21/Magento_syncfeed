<?php
namespace DRS\SyncFeed\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use DRS\SyncFeed\Model\CategoryMatchFactory;
use DRS\SyncFeed\Model\ProductStorage;

abstract class Match extends Action
{	
	protected $_resultPageFactory;
	protected $_categorymatchFactory;
	
	public function __construct(
		Context $context,
		PageFactory $resultPageFactory,
		Registry $coreRegistry,
		CategoryMatchFactory $categorymatchFactory,
		ProductStorage $productStorage
		)
	{
		parent::__construct($context);
		$this->_coreRegistry = $coreRegistry;
		$this->_resultPageFactory = $resultPageFactory;
		$this->_categorymatchFactory = $categorymatchFactory;
		$this->_productStorage = $productStorage;
	}

	protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('DRS_SyncFeed::Match_Category');
    }
}

?>