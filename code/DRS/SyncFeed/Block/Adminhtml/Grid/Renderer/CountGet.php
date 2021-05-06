<?php
namespace DRS\SyncFeed\Block\Adminhtml\Grid\Renderer;

use DRS\SyncFeed\Model\Config\Source\ProductCount;

class CountGet extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
   protected $_productCount;

   public function __construct(ProductCount $productCount)
   {
     $this->_productCount = $productCount;
   }

public function render(\Magento\Framework\DataObject $row)
{
	// Get value to grid for filter
   	$category_origin = parent::render($row);

   	$countItem = $this->_productCount->getCount($category_origin);

	// Return value to grid
   	return $countItem;
  }
}