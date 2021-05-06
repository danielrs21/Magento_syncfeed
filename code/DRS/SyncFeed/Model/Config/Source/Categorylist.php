<?php

namespace DRS\SyncFeed\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
class Categorylist extends AbstractElement implements ArrayInterface
{
    protected $_categoryFactory;
    protected $_categoryCollectionFactory;

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
    )
    {
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    public function getCategoryCollection(
        $isActive = false, 
        $level = false, 
        $sortBy = false, 
        $pageSize = false, 
        $filter = false, 
        $filterValue = false,   
        $attrSelect = '*'
    )
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect($attrSelect);

        // Filter result
        if ($filter && $filterValue) {
            $collection->addAttributeToFilter($filter, $filterValue);
        }

        // select only active categories
        if ($isActive) {
            $collection->addIsActiveFilter();
        }

        // select categories of certain level
        if ($level) {
            $collection->addLevelFilter($level);
        }

        // sort categories by some value
        if ($sortBy) {
            $collection->addOrderField($sortBy);
        }

        // select certain number of categories
        if ($pageSize) {
            $collection->setPageSize($pageSize);
        }

        return $collection;
    }

    public function toOptionArray()
    {
        $arr = $this->_toArray();
        $ret = [];

        foreach ($arr as $key => $value)
        {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    public function getValidIds($catIds){
        $ids = array();
        
        $categories = $this->getCategoryCollection( false, false, false , false, 'entity_id', array('in' => explode(',',$catIds)) );

        foreach ($categories as $cat) {
           $ids[] = $cat->getId();
        }

        return $ids;
    }

    public function getAllIds(){
        $catIds = array();
        $categories = $this->getCategoryCollection( false, false, false , false, false, false, "entity_id");

        foreach ($categories as $cat) {
            $catIds[] = $cat->getEntityId();
        }

        return $catIds;
    }

    public function getCat($catIds)
    {
        $strCat = '';
        $categories = $this->getCategoryCollection( false, false, false , false, 'entity_id', array('in' => explode(',',$catIds)) );

        foreach ($categories as $cat) {
           $strCat.= $cat->getName().'<br> ';
        }

        return $strCat;
    }
    /* 
    * Retorna un array de las categorias del arbol completo de producto con datos adicionales
    */
    public function getCatPathDetails(array $catIds)
    {
        $catArray = array();
        $registered = array();
        $categories = $this->getCategoryCollection( false, false, false , false, 'entity_id', array('in' => $catIds) );

        foreach ($categories as $cat) {
            $catPath = explode( '/', $cat->getPath() );

            /* Eliminar categoria root */
            unset($catPath[0]);
            $parentCat = $cat;

            /* Obtener detalles de categorias superiores */
            for ($i = 1; $i < count($catPath); $i++) { 

                $parentCat = $parentCat->getParentCategory();

                /* Se evita registros duplicados */
                if( !in_array( $parentCat->getId() , $registered ) ) {
                    $catArray[] = array (
                        'cat_name'      => $parentCat->getName(),
                        'cat_id'        => $parentCat->getId(),
                        'cat_url_key'   => $parentCat->getUrlKey(),
                        'parent_id'     => $parentCat->getParentId()
                    );

                    $registered[] = $parentCat->getId();
                }

            }

            $catArray = array_reverse($catArray);

            /* Obtener detalles de categoria base */
            if( !in_array( $cat->getId() , $registered ) ) {
                $catArray[] = array (
                    'cat_name'      => $cat->getName(),
                    'cat_id'        => $cat->getId(),
                    'cat_url_key'   => $cat->getUrlKey(),
                    'parent_id'     => $cat->getParentId()
                );
                $registered[] = $cat->getId();
            }

        }

        return $catArray;
    }

    /* Obtiene el id de categoria en base al string completo de categoria: Ej. Siftdeals/Electronics/Computer */ 
    public function getIdByName(array $catNames){
        if($catNames[0] == 'Siftdeals') {
            $catNames[0] = 'Siftty';
        }

        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToFilter('name',trim($catNames[0]))->setPageSize(1); 
        $category = $collection->getFirstItem();
        $catId = 2; // Default category 

        for ($i = 1; $i < count($catNames); $i++) { 

            $subcategories = $category->getChildrenCategories();

            foreach ($subcategories as $sub) {
                //echo $sub->getName() .' == '. $catNames[$i].PHP_EOL;
                if($sub->getName() == $catNames[$i]){
                    $catId = $sub->getId();
                    $category = $sub;
                    continue(2);
                }

            }

        }

        return $catId;        
    }

    private function _toArray()
    {
        $categories = $this->getCategoryCollection(false, false, 'path', false);

        $catagoryList = array();
        foreach ($categories as $category)
        {
            if($category->getEntityId() >= 2 ){
                $catagoryList[$category->getEntityId()] = __($this->_getParentName($category->getPath()) . $category->getName());
            }
        }

        return $catagoryList;
    }

    private function _getParentName($path = '')
    {
        $parentName = '';
        $rootCats = array(1,2);

        $catTree = explode("/", $path);
        // Deleting category itself
        array_pop($catTree);

        if($catTree && (count($catTree) > count($rootCats)))
        {
            foreach ($catTree as $catId)
            {
                if(!in_array($catId, $rootCats))
                {
                    $category = $this->_categoryFactory->create()->load($catId);
                    $categoryName = $category->getName();
                    $parentName .= $categoryName . ' > ';
                }
            }
        }
        
        return $parentName;
    }
}