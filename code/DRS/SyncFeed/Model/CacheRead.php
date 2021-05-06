<?php
 
namespace DRS\SyncFeed\Model;

use Magento\Framework\App\ResourceConnection;

class CacheRead 
{

	const TABLE_CACHE = 'drs_syncfeed_cache';

	const TABLE_CATEGORY_MATCH = 'drs_syncfeed_categorymatch';

	protected $resource;

	public function __construct(ResourceConnection $resource){
		$this->resource = $resource;
	}

	/**
	*	Obtiene conteo de items de la cache externa
	*	
	*	@param string  $filters Filtros a aplicar al select. 
	*/
	public function getCount($filters=false){

		$connection = $this->resource->getConnection();
		$table = $this->resource->getTableName(self::TABLE_CACHE);
		$sql = 'SELECT count(*) as items FROM '.$table;
		if($filters){
			$sql.= ' WHERE '.$filters;
		}

        $result = $connection->fetchAll($sql); 

        // Se convierte el array simple en array object
        return $result{0}['items'];

	}

	/**
	*	Obtiene items de la cache externa
	*	
	*	@param integer $limit Limite de registros de la petición
	*	@param integer $offset Registros iniciales a omitir en la petición. Para paginacion.
	*	@param string  $filters Filtros a aplicar al select. 
	*/
	public function getItems( $limit=100, $offset=0, $filters=false ){

		$connection = $this->resource->getConnection();
		$table = $this->resource->getTableName(self::TABLE_CACHE);

		$sql = 'SELECT * FROM '.$table;
		if($filters){
			$sql.= ' WHERE '.$filters;
		}
		$sql.= ' LIMIT '.$limit.' OFFSET '.$offset;

        $result = $connection->fetchAll($sql); 

        // Se convierte el array simple en array object
        return json_decode(json_encode($result));

	}

	/**
	*	Marca Item como creado en magento
	*	
	*	@param integer $item_id Id Item en cache
	*	return void
	*/
	public function setCreated($item_id,$product_id){
		$connection = $this->resource->getConnection();
		$table = $this->resource->getTableName(self::TABLE_CACHE);
		$sql = 'UPDATE '.$table.' SET sync_new = 0, magento_id = '.$product_id.' WHERE item_id='.$item_id;
		$connection->query($sql);
	}

	/**
	*	Marca Item como creado en magento en modo actualización
	*	
	*	@param array $items pares item_id, entity_id
	*	return void
	*/
	public function setUpdated(array $items){
		$connection = $this->resource->getConnection();
		$table = $this->resource->getTableName(self::TABLE_CACHE);

		foreach($items as $item){
			$sql = 'UPDATE '.$table.' SET sync_new = 0, magento_id = '.$item['entity_id'].' WHERE item_id='.$item['item_id'];
			$connection->query($sql);			
		}
	}

	/**
	*	Obtiene Ids de categorias magento si existe en tabla de conversion de cache
	*	
	*	@param string $category Id Item en cache
	*	return string or false
	*/
	public function getCategory($category){
		$connection = $this->resource->getConnection();
		$table = $this->resource->getTableName(self::TABLE_CATEGORY_MATCH);
		$sql = 'SELECT category_match as categories FROM '.$table.' WHERE category_feed ="'.$category.'" AND status = 1';
		$result = $connection->fetchAll($sql); 
		if($result){
			return $result{0}['categories'];
		} else {
			return false;
		}
	}

	public function deleteMassProducts($deleteIds){
		$deleteIds = implode(',',$deleteIds);
		$connection = $this->resource->getConnection();

		$table = $this->resource->getTableName(self::TABLE_CACHE);
		
		$queryStr = 'DELETE FROM '.$table.' WHERE item_id in('.$deleteIds.')';
		
		$connection->query($queryStr);
	}
}

