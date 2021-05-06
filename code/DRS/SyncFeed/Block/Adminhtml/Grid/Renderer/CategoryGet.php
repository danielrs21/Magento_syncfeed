<?php
namespace DRS\SyncFeed\Block\Adminhtml\Grid\Renderer;

use DRS\SyncFeed\Model\Config\Source\Categorylist;

class CategoryGet extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
   protected $_categoryList;

   public function __construct(Categorylist $categoryList)
   {
     $this->_categoryList = $categoryList;
   }

public function render(\Magento\Framework\DataObject $row)
{
	// Get value to grid for filter
   	$category_match = parent::render($row);

   	$categoryValues = $this->_categoryList->getCat($category_match);

	// Return value to grid
   	return $categoryValues;
  }
}