<?php
 
namespace DRS\SyncFeed\Model\Reindex;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use DRS\SyncFeed\Model\CategoryProductFactory;
use DRS\SyncFeed\Model\CategoryMatchFactory;
use DRS\SyncFeed\Model\Config\Source\Categorylist;
use Magento\Framework\App\ResourceConnection;

class CategoryMatch 
{
	/** 
	 * @var Product Collection Factory $_productCollection
	 */
	protected $_productCollection;

	/** 
	 * @var Category Product Factory $_categoryProduct
	 */
	protected $_categoryProduct;

	/** 
	 * @var Category Match Factory $_categoryMatch
	 */
	protected $_categoryMatch;

	/** 
	 * @var Category List $_categoryList
	 */
	protected $_categoryList;

	/** 
	 * @var Resource $_resource
	 */
	protected $_resource;

	/** 
	 * @var Insert Batch $_insertBatch
	 */
	protected $_insertBatch = array();

	/** 
	 * @var Delete Ids $_deleteIds
	 */
	protected $_deleteIds = array();

	public function __construct(
			CollectionFactory $productCollection,
			CategoryProductFactory $categoryProduct,
			CategoryMatchFactory $categoryMatch,
			Categorylist $categoryList,
			ResourceConnection $resource
		){
		$this->_productCollection = $productCollection; 
		$this->_categoryProduct = $categoryProduct; 
		$this->_categoryMatch = $categoryMatch; 
		$this->_categoryList = $categoryList; 
		$this->_resource = $resource;
		parent::__construct();
	}

	private function reindexCategory($category_origin=false){

		// Obtener productos magento
		$collection = $this->_productCollection->create();
		$collection->addAttributeToSelect('category_origin');
		if($category_origin)
		{
			$collection->addFieldToFilter('category_origin',$category_origin);			
		}

		$catIds = $this->_categoryList->getAllIds();

		$updated = 0; $equal = 0; $error = 0; $deleted = 0; $categoryNotExist = 0; $notLinked = 0;

	    foreach($collection as $_product){	

	    	// Obtiene categorias asociadas actualmente
	    	$actualCategoryIds = $_product->getCategoryIds();

	    	// Obtiene categorias asociadas en Match Category
	    	$category_ids = $this->getCategoryIds($_product->getData('category_origin'));

			// Obtener coleccion de category_products filtrando por el producto actual
			$categoryProduct = $this->_categoryProduct->create()->getCollection();
			$categoryProduct->addFieldToFilter( 'product_id' , $_product->getEntityId() );

	    	// Si existen categorias en Category Match se procesa, sino se eliminan asociaciones en el producto.
	    	if($category_ids){

	    		$category_ids = explode(',',$category_ids);

	    		// Se verifican si son distintas las categorias asignadas y por asignar.
		    	if( $actualCategoryIds == $category_ids ) {
		    		$equal++;
		    		continue;
		    	} else {
		    		try {
		    			// Ubicar categorias pre asignadas que deben eliminarse
		    			foreach ( (array_diff($actualCategoryIds, $category_ids ) ) as $cat) 
		    			{
							// Eliminar asociaciones actuales
							foreach ($categoryProduct as $item) 
							{
								if($item->getCategoryId() == $cat)
								{
									$this->_deleteIds[] = $item->getEntityId;
									$deleted++;
								}
							}
		    			}

		    			// Obtener categorias que deben agregarse
		    			foreach ( (array_diff($category_ids, $actualCategoryIds ) ) as $newCat) {
							// Verifica si la categoria existe en magento						
							if(in_array( $newCat , $catIds )) {	
								// Se incluye registro para insertar en batch
								$this->addInsert($newCat,$_product->getEntityId(),0);
								$updated++;
							} else {
								$categoryNotExist++;
							}	
		    			}				

					} catch (Exception $e) {
						$error++;
					}
		    	}
	    	} else {
	    		$notLinked++;
				// Eliminar asociaciones actuales ya que en Category Match no existe ninguna asociación.
				foreach ($categoryProduct as $item) {
					$this->_deleteIds[] = $item->getEntityId;
					$deleted++;
				}

	    	}

 	    	// OPCIONAL: Se seccionan las peticiones batch a un limite de registros 
	    	if(count($this->_deleteIds) >= 5000 ) {
	    		$this->sendDelete();
	    	}
	    	if(count($this->_insertBatch) >= 5000 ) {
	    		$this->sendData();
	    	}
	    }

	    // Se envian los datos pendientes por almacenar en DB
    	if(count($this->_deleteIds)>0) {
    		$this->sendDelete();
    	}
	    if(count($this->_insertBatch)>0)
	    {
			$this->sendData();
		}

	    return true;
	}

	private function addInsert( $category_id, $product_id, $position )
	{
		$this->_insertBatch[] = 	[
					        		'category_id' 	=> $category_id,
					        		'product_id' 	=> $product_id,
					        		'position'	 	=> $position
    							];
	}

	private function sendDelete(){
		$connection = $this->_resource->getConnection();
		$deleteIds = implode(',',$this->_deleteIds);
        try {
            $connection->query('DELETE FROM catalog_category_product WHERE entity_id in('.$deleteIds.')');
	        unset($this->_deleteIds); $deleteIds = '';
			$this->_deleteIds = array();

		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

    /**
     * Envia inserts en batch a la DB
     *
     * @return void
     */
	private function sendData(){
		$connection = $this->_resource->getConnection();
        try {
            $connection->insertMultiple('catalog_category_product', $this->_insertBatch);
	        unset($this->_insertBatch);
			$this->_insertBatch = array();
        } catch (\Exception $e) {
			return $e->getMessage();
        }
	}

    /**
     * Devuelve Ids de categorias asociadas en Category Match
     *
     * @param $categoryOrigin Valor de Categoria de Origen del producto a buscar
     *
     * @return void
     */
	private function getCategoryIds($categoryOrigin)
	{
		$connection = $this->_resource->getConnection();

		$sql = ' 
			SELECT 
				category_match 
			FROM 
				drs_syncfeed_categorymatch 
			WHERE 
				category_feed = "'.$categoryOrigin.'"';

        $result = $connection->fetchAll($sql); 
        if($result){
        	return $result{0}['category_match'];
        } else {
        	return false;
        }
	}
}