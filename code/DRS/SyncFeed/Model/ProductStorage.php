<?php
 
namespace DRS\SyncFeed\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use DRS\SyncFeed\Helper\AttributeCheck;
use DRS\SyncFeed\Model\Config\Source\Categorylist;
use DRS\SyncFeed\Model\CacheRead;
use DRS\SyncFeed\Service\ImportImageService;
use DRS\SyncFeed\Logger\SyncLogger;
use DRS\SyncFeed\Logger\SyncLogQuery;

class ProductStorage 
{

	const STORE_ID = 0; /* No aplica sobre URL Rewrite */ 
     
	const WEBSITE_ID = 1;

	const DEFAULT_ROOT_CATEGORY = 2;

	const PRODUCT_BASE_TABLE 		= 'catalog_product';
	const PRODUCT_ENTITY_TABLE 		= 'catalog_product_entity';
	const PRODUCT_WEBSITE_TABLE 	= 'catalog_product_website';
	const INVENTORY_STOCK 			= 'cataloginventory_stock_item';
	const INVENTORY_STOCK_STATUS 	= 'cataloginventory_stock_status';
	const PRODUCT_CATEGORY_TABLE 	= 'catalog_category_product';
	const ATTRIBUTE_OPTION 			= 'eav_attribute_option'; 
	const ATTRIBUTE_OPTION_VALUE 	= 'eav_attribute_option_value';
	const URL_REWRITE 				= 'url_rewrite';
	const CATEGORY_URL_REWRITE 		= 'catalog_url_rewrite_product_category';
	const CACHE_PRODUCTS			= 'drs_syncfeed_cache';

	/** 
	 * @var SyncLogger $syncLogger
	 */
	protected $_syncLogger;

	protected $_syncLogQuery;

	protected $_resource;

	protected $_attributeInfo;

	protected $_attributeRepository;

	protected $_importimageservice;

	protected $_productRepository;

	protected $_timezoneInterface;

	public function __construct(
		ResourceConnection $resource, 
		ProductRepository $productRepository,
		CollectionFactory $productCollectionFactory,
		AttributeRepository $attributeRepository, 
		AttributeCheck $attributeCheck, 
		Categorylist $categoryList,
		CacheRead $cacheRead,
		ImportImageService $importimageservice, 
		TimezoneInterface $timezoneInterface,
		SyncLogger $syncLogger,
		SyncLogQuery $syncLogQuery
	){

		$this->_resource 					= $resource;
		$this->_productRepository			= $productRepository;
		$this->_productCollectionRepository = $productCollectionFactory;
		$this->_attributeRepository 		= $attributeRepository;
		$this->_attributeCheck 				= $attributeCheck;
		$this->_categoryList				= $categoryList;
		$this->_cacheRead					= $cacheRead;
		$this->_importimageservice			= $importimageservice;
		$this->_timezoneInterface			= $timezoneInterface;
		$this->_syncLogger 					= $syncLogger;
		$this->_syncLogQuery				= $syncLogQuery;

		/* Obtener atribute set del modulo */
		$this->_attributeSetId[1] = $this->_attributeCheck->getAttributeSet('Affiliate');
		$this->_attributeSetId[2] = $this->_attributeCheck->getAttributeSet('Coupons');
		$this->_attributeSetId[3] = $this->_attributeCheck->getAttributeSet('Resold');

	}

    public function assignCategoryProduct(array $catIds, $category_origin){
    	$productIds = array();

        $collection = $this->_productCollectionRepository->create();
	    $collection->addAttributeToFilter('category_origin', $category_origin);
	    $collection->addAttributeToSelect(array('category_ids'));

	    foreach ($collection as $product) {
    		$productIds[] = $product->getEntityId();
	    }

	    $connection = $this->_resource->getConnection();

		$productEntity 		= $this->_resource->getTableName(self::PRODUCT_ENTITY_TABLE);
	    $categoryProduct 	= $this->_resource->getTableName(self::PRODUCT_CATEGORY_TABLE);

		$total = 0;
	    $ids = implode(',',$productIds);
		if( count($productIds) > 0 ) {

			/* Se eliminan asignaciones de categorias actuales */
			$queryStr = 'DELETE FROM '.$categoryProduct.' WHERE product_id in('.$ids.');';
			$connection->query($queryStr);

			/* Se asignan nuevas categorias */
			foreach ($catIds as $cat) {
				$queryStr = 'INSERT IGNORE INTO '.$categoryProduct.' (category_id, product_id) 
								SELECT '.$cat.', entity_id FROM '.$productEntity.' WHERE entity_id in('.$ids.');';

				$result = $connection->query($queryStr);

				if( $result->rowCount() > 0 ){
					$total = $total + $result->rowCount();
				} else {
					$total = false;
				}	
			}
		}

    	return $total;

    }

	public function createProduct(&$item, $loadImage = false ) {
		$this->getAttributeData();

		/* Construir arreglo con toda la información */
		$product = array();

		$product['attribute_set_id'] = $this->_attributeSetId[$item->item_type_id]; 

		if( $item->item_type_id == 1 || $item->item_type_id == 2 ){
			$product['attribute']['seller_id'] = $item->store_public_id;
		} elseif($item->item_type_id==3){
			$product['attribute']['shipping_cost'] = $item->shipping_cost;
		}

		/* PRODUCTOS AFILIADOS O CUPONES */
		if( $item->external_category == 1 ) {
		//if( $item->item_type_id == 1 || $item->item_type_id == 2 ){
				
			//$product['attribute']['seller_id'] = $item->store_public_id;		
			
			/* Verifica y devuelve las categorias asociadas en Category Match */
			$category_ids = $this->_cacheRead->getCategory(trim($item->category));

			if($category_ids){
				/* Devuelve las Ids de categorias validas */ 
				$product['category_ids'] = $this->_categoryList->getValidIds($category_ids);
			} else {
				$product['category_ids'] = array(self::DEFAULT_ROOT_CATEGORY);
			}

		/* PRODUCTOS RESOLD */ 
		} else { //if($item->item_type_id==3){
			
			//$product['attribute']['shipping_cost'] = $item->shipping_cost;
		
			$categories = explode(",",trim($item->category));

			/* Se buscan las categorias magento y se asigna el id al array */
			foreach($categories as $cat){

				$cats = explode('/',trim($cat));

				if( count($cats) > 1 ) {
					$product['category_ids'][] = $this->_categoryList->getIdByName($cats);
				} 

			}

			/* Si no hay categorias se asigna la raiz por defecto */ 
			if( count( $product['category_ids'] ) == 0 ) {
				//return 'error';
				$product['category_ids'] = array(self::DEFAULT_ROOT_CATEGORY);
			}

			$product['category_ids'] = array_unique($product['category_ids']);

		}

		if(trim($item->manufacturer_name)){
			$manufacturerId = $this->_attributeCheck->createOrGetId('manufacturer', strtoupper( trim( str_replace('"','',$item->manufacturer_name) ) ) );
		} else {
			$manufacturerId = $this->_attributeCheck->createOrGetId('manufacturer', "OTHER");
		}
		
		$product['sku'] 		= $item->item_code;		
		$product['type_id'] 	= 'simple';	
		$product['website_id'] 	= self::WEBSITE_ID;		

		/* Attributos simples */  
		$product['attribute']['category_origin'] = $item->category;
		$product['attribute']['name'] 						= trim($item->item_title);
		$product['attribute']['description'] 				= trim(stripcslashes($item->item_description));
		$product['attribute']['url_key'] 					= trim($item->item_url_slug);
		$product['attribute']['product_sku'] 				= trim($item->item_sku);
		$product['attribute']['upc'] 						= trim($item->item_upc);
		$product['attribute']['manufacturer'] 				= $manufacturerId;
		$product['attribute']['manufacturer_sku'] 			= trim($item->manufacturer_sku);
		$product['attribute']['price'] 						= $item->item_last_normal_price;
		$product['attribute']['special_price'] 				= $item->item_last_price;
		$product['attribute']['buy_url'] 					= trim($item->item_buy_url);
		$product['attribute']['image_external_url'] 		= trim($item->item_image_url);
		$product['attribute']['seller_name'] 				= trim($item->store_name);
		$product['attribute']['status'] 					= 1; /* VISIBLE */	
		$product['attribute']['visibility'] 				= 4; /* CATALOG, SEARCH */
		$product['attribute']['quantity_and_stock_status']	= 1; /* Stock true */ 
		$product['attribute']['tax_class_id']				= 2; /* TAXABLE GOODS */
		$product['attribute']['meta_keyword']				= trim($item->item_title);
		$product['attribute']['meta_title']					= trim($item->item_title);
		$product['attribute']['meta_description']			= trim($item->item_title);
		$product['attribute']['options_container']			= 'container2'; /* Display Product Options In Block after info column */
		$product['attribute']['gift_message_available']		= 2; /* No Gift */ 
		$product['attribute']['special_from_date']			= $this->_timezoneInterface->date()->format('y/m/d H:i:s');

		/* Información de Stock */
		$product['stock'][] =   array(
									'stock_id' 					=> 1,
		                            'use_config_manage_stock'	=> 0,
		                            'manage_stock' 				=> 0,
		                            'is_in_stock' 				=> 1,
		                            'qty' 						=> 1
		                        );

		$product['stock_status'][] =    array(
				                            'website_id' 	=> 0,
				                            'stock_id' 		=> 1,
				                            'qty' 			=> 1,
				                            'stock_status' 	=> 1
				                        );    
		
		/* 
		*	PROCESO DE REGISTRO DE PRODUCTO EN BASE DE DATOS - SIN USAR MODELOS MAGENTO
		*
		*	Se realiza utilizando transacciones a fin de preservar la integridad de la BD. 
		*
		*	Para que un producto sea registrado debe cumplir en cascada todas los registros en tablas.
		*/
		try {
			/* Variable para determinar si se debe hacer commit o rollback */
			$valid = false; 

			$connection = $this->_resource->getConnection();
			$connection->beginTransaction();

			/* INSERT DATOS DE PRODUCTO EN TABLA PRINCIPAL */
			$result = $this->insertProduct($product, $connection);

			if($result) {

				/* INSERT DE ATRIBUTOS */
				$entityId = $connection->lastInsertId();
				$valid = true;

				$arrayAttribute = array();
				foreach ($product['attribute'] as $attribute => $value) {

					$suffix = $this->_attributeInfo[$attribute]['suffix'];

					$arrayAttribute[$suffix][] = array (
						'attribute_id'  => 	$this->_attributeInfo[$attribute]['attribute_id'],
						'store_id'      => 	self::STORE_ID,
						'entity_id'     => 	$entityId,
						'value'         => 	$value
					);

				}

				$result = $this->updateMassProductAttributes( $arrayAttribute , array('value') , $connection );

				if($result){
					
					/* INSERT DE VALORES DE STOCK */
					$product['stock'][0]['product_id'] = $entityId;
					$result = $this->insertProductStockInfo( $product['stock'][0] , $connection );

					if($result){

						/* INSERT DE VALORES DE STOCK STATUS */
						$product['stock_status'][0]['product_id'] = $entityId;
						$result = $this->insertProductStockStatus( $product['stock_status'][0] , $connection );						

						if($result) {
						
							/* INSERT PARA ASOCIAR CON CATEGORIAS */
							$catInsert = 0;

							foreach ($product['category_ids'] as $category) {
								$result = $this->insertProductInCategory( 
												array(
										        	'category_id' 	=> $category,
										        	'product_id' 	=> $entityId,
										        	'position'	 	=> 0 // Default
					    						),
												$connection
											);
								if($result) $catInsert++;
							}

							if(count($product['category_ids']) == $catInsert) {

								/* INSERT PARA ASOCIAR PRODUCTO A WEBSITE */
								$result = $this->insertProductInWebsite( 
									array( 
										'product_id' => $entityId, 
										'website_id' => $product['website_id']
									),
									$connection
								);

								if($result) {

									/* INSERT EN URL_REWRITE */
									$result = $this->generateUrlRewrite( $product, $entityId, $connection );

									if($result) {

										/* Solo al cumplirse el ultimo insert se marca la transacción como valida */
										$valid = true;

									} /* FIN INSERT URL_REWRITE */

								} /* FIN INSERT WEBSITE */

							} /* FIN INSERT CATEGORIAS */

						} /* FIN DE INSERT STOCK STATUS */ 

					} /* FIN INSERT STOCK */ 

				} /* FIN INSERT ATRIBUTOS */

			} /* FIN INSERT CABECERA */

			if($valid) {
				$connection->commit();
				$this->_syncLogger->info('Nuevo producto registrado: '.$product['sku']);

				/* Actualiza tabla cache para indicar que ya el producto ha sido creado en magento */
				$this->_cacheRead->setCreated($item->item_id, $entityId);

				/* Descarga y asignacion imagen a producto */
				if($loadImage){
					$product = $this->getProductBySku($product['sku']);
					if($product) {
						if($product->getImage() == NULL or $product->getImage() == 'no_selection') {
							$this->_importimageservice->loadImageToProduct(
								$product, 
								false,
								array('image', 'small_image', 'thumbnail'),
								true
							);		
						}				
					}
				}
				return 'created';
			} else {
				$connection->rollBack();
				$this->_syncLogger->error('No se logro insertar el producto: '.$product['sku']);
				return 'error';
			}

		}	
		catch (Exception $e) {
			$connection->rollBack();
			$this->_syncLogger->error($e->getMessage());
		} 
	}

	public function generateUrlRewrite(array $product, $entityId, $connection){

		$targetString = 'catalog/product/view/id/';

		/* Se registra primero el rewrite del producto solo */ 
		$records[] = array (
			'entity_type' 		=> 'product',
			'entity_id' 		=> $entityId,
			'request_path' 		=> $product['attribute']['url_key'],
			'target_path' 		=> $targetString.$entityId,
			'store_id' 			=> 1, //self::STORE_ID,
			'is_autogenerated'	=> 1,
			'metadata'			=> NULL
		);

		/* Se generan las url para el producto asociado a categorias */
		$categories = $product['category_ids'];
		//$categories = array(373,543,366);

		$registered = array();

		foreach ($categories as $category) {
			$catArray = $this->_categoryList->getCatPathDetails( array($category) );
			$urlkey = false;

			foreach ($catArray as $cat) {

				/* Se construye el request_path */
				if($cat['cat_id'] == 2){
					$request_path = '/' . $product['attribute']['url_key'];
				} else {
					if($urlkey) {
						$urlkey.= '/' . $cat['cat_url_key'];
					} else {
						$urlkey = $cat['cat_url_key'];
					}
					$request_path = $urlkey . '/' . $product['attribute']['url_key'];
				}

				/* Se construye el arreglo para enviar a la DB */
				if(!in_array($cat['cat_id'], $registered)){
				
					$records[] = array (
						'entity_type' 		=> 'product',
						'entity_id' 		=> $entityId,
						'request_path' 		=> $request_path,
						'target_path' 		=> $targetString.$entityId.'/category/'.$cat['cat_id'],
						'store_id' 			=> 1,
						'is_autogenerated'	=> 1,
						'metadata'			=> '{"category_id":"'.$cat['cat_id'].'"}',
						'category_id'		=> $cat['cat_id']	
					);
					$registered[] = $cat['cat_id'];
				
				}

			}

		}

		$total = 0;
		foreach ($records as $record) {
			$record_actual = $record;

			/* Elimino la llave category_id para evitar conflicto en el insert */
			unset($record_actual['category_id']);

			/* Se insertan los URL Rewrite del producto */
			$result = $this->insertUrlRewrite($record_actual, $connection);

			if($result)	{

				$total = $total + $result;

				/* Omito el registro de producto base */
				if($record['metadata']){
					$url_rewrite_id = $connection->lastInsertId();

					/* Se inserta la asociación de URL Rewrite con Categorias */
					$this->insertCategoryUrlRewrite(  
						array(
							'url_rewrite_id'	=> $url_rewrite_id,
							'category_id'		=> $record['category_id'],
							'product_id'		=> $entityId
						)
					);
				}
			}
		}
		return $total;
	}

	/*
	*	Inserta los datos principales del producto
	*	
	*	@param Array $product Pares de campos - valor a insertar
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return Rows affected
	*/
	public function insertProduct(array $product, $connection = false){

		if(!$connection){
			$connection = $this->_resource->getConnection();
		}

		$table = $this->_resource->getTableName(self::PRODUCT_ENTITY_TABLE);

		$queryStr = "INSERT INTO $table ( attribute_set_id, type_id, sku ) 
						VALUES ( :attribute_set_id, :type_id, :sku )";

		$bind = array(
						'attribute_set_id' 	=> $product['attribute_set_id'],
						'type_id'		   	=> $product['type_id'],
						'sku'				=> $product['sku']
					);
		return $this->runQuery($queryStr, $bind, $connection);

	}

	/*
	*	Inserta los datos de Stock del producto
	*	
	*	@param Array $bind Pares de campos - valor a insertar
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return Rows affected
	*/
	public function insertProductStockInfo($bind, $connection = false){

		if(!$connection){
			$connection = $this->_resource->getConnection();
		}

		$table = $this->_resource->getTableName(self::INVENTORY_STOCK);

		$queryStr = "INSERT INTO $table ( product_id, stock_id, use_config_manage_stock, manage_stock, is_in_stock, qty ) 
						VALUES ( :product_id, :stock_id, :use_config_manage_stock, :manage_stock, :is_in_stock, :qty )";	

		return $this->runQuery($queryStr, $bind, $connection);

	}

	/*
	*	Inserta los datos de Status Stock del producto
	*	
	*	@param Array $bind Pares de campos - valor a insertar
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return Rows affected
	*/
	public function insertProductStockStatus($bind, $connection = false){

		if(!$connection){
			$connection = $this->_resource->getConnection();
		}

		$table = $this->_resource->getTableName(self::INVENTORY_STOCK_STATUS);

		$queryStr = "INSERT INTO $table ( product_id, website_id, stock_id, qty, stock_status ) 
						VALUES ( :product_id, :website_id, :stock_id, :qty, :stock_status )";	

		return $this->runQuery($queryStr, $bind, $connection);

	}

	/*
	*	Asocia el producto a categorias
	*	
	*	@param Array $bind Pares de campos - valor a insertar
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return Rows affected
	*/
	public function insertProductInCategory(array $bind, $connection = false) {

		if(!$connection){
			$connection = $this->_resource->getConnection();
		}

		$table = $this->_resource->getTableName(self::PRODUCT_CATEGORY_TABLE);

		$queryStr = "INSERT INTO $table ( category_id, product_id, position ) 
						VALUES ( :category_id, :product_id, :position )";

		return $this->runQuery($queryStr, $bind, $connection);

	}

	public function updateMassProductInCategory(array $data){

		foreach($data as $record) {
			$this->insertProductInCategory($record);
		}

	}

	public function deleteMassProductInCategory(array $data){

		$table = $this->_resource->getTableName(self::PRODUCT_CATEGORY_TABLE);
		$connection = $this->_resource->getConnection();

		foreach($data as $record) {

			$queryStr = 'DELETE FROM '.$table.' WHERE 
				product_id = '.$record['product_id'].' AND 
				category_id = '.$record['category_id'];

			$connection->query($queryStr);
		}

	}

	/*
	*	Asocia el producto a website
	*	
	*	@param Array $bind Pares de campos - valor a insertar
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return Rows affected
	*/
	public function insertProductInWebsite(array $bind, $connection = false){

		if(!$connection){
			$connection = $this->_resource->getConnection();
		}

		$table = $this->_resource->getTableName(self::PRODUCT_WEBSITE_TABLE);	

		$queryStr = "INSERT INTO $table ( product_id, website_id ) 
						VALUES ( :product_id, :website_id )";

		return $this->runQuery($queryStr, $bind, $connection);
	}

	/*
	*	Insert URL Rewrite del producto
	*	
	*	@param Array $bind Pares de campos - valor a insertar
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return Rows affected
	*/
	public function insertUrlRewrite(array $bind, $connection = false){

		if(!$connection){
			$connection = $this->_resource->getConnection();
		}

		$table = $this->_resource->getTableName(self::URL_REWRITE);	
										 			
		$queryStr = "INSERT INTO $table 
								( entity_type, entity_id, request_path, target_path, store_id, is_autogenerated, metadata ) 
						VALUES 	( :entity_type, :entity_id, :request_path, :target_path, :store_id, :is_autogenerated, :metadata )";

		return $this->runQuery($queryStr, $bind, $connection);

	}

	/*
	*	Insert Category URL Rewrite del producto
	*	
	*	@param Array $bind Pares de campos - valor a insertar
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return Rows affected
	*/
	public function insertCategoryUrlRewrite(array $bind, $connection = false){

		if(!$connection){
			$connection = $this->_resource->getConnection();
		}

		$table = $this->_resource->getTableName(self::CATEGORY_URL_REWRITE);	

		$queryStr = "INSERT INTO $table 
								( url_rewrite_id, category_id, product_id ) 
						VALUES 	( :url_rewrite_id, :category_id, :product_id )";

		return $this->runQuery($queryStr, $bind, $connection);

	}

	/*
	*	Ejecuta un query
	*	
	*	@param $queryStr Query String a ejecutar 
	*	@param $bind Arreglo de campo - valor a insertar con el query string
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return Rows affected
	*/
	private function runQuery($queryStr, $bind, $connection){
		$maxRetry = 4; $retry = 0; $timeout = 10;

		try {
			retry: 
			$this->_syncLogQuery->info('QUERY: '.$queryStr);
			$this->_syncLogQuery->info('VALUES: '.implode(',',$bind));
			$result = $connection->query( $queryStr , $bind );
			if( $result->rowCount() > 0 ){
				return $result->rowCount();
			} else {
				return false;
			}	
		} catch (\Magento\Framework\DB\Adapter\DeadlockException $e) {
			$retry++;
			if( $maxRetry >= $retry ) {
				echo 'Retry process query '.$retry.PHP_EOL;
				print_r($queryStr);
				print_r($bind);
				sleep($timeout);
				goto retry;
			} else {
				echo 'ERROR - DEADLOCK EXCEPTION: '.$e->getMessage().PHP_EOL;
				$this->_syncLogger->error($e->getMessage().' Query String: '.$queryStr);
				$connection->rollback();
				exit;
			}
		} catch (Zend_Db_Statement_Exception $e) {
		    $this->_syncLogger->error('Error integridad al insertar el producto '.$e->getMessage(). 'Query String: '.$queryStr);
			//$connection->rollback();
			return false;
		} catch (\Magento\Framework\DB\Adapter\DuplicateException $e){
			$this->_syncLogger->error('Error duplicado en registro producto '.$e->getMessage(). 'Query String: '.$queryStr);
			return false; 
		}
	}

	/*
	*	Elimina productos por Id
	*	
	*	@param $deleteIds Ids de productos separados por coma ","
	*	@return rows affected
	*/
	public function deleteMassProducts($deleteIds){
		$connection = $this->_resource->getConnection();
		$deleteIds = implode(',',$deleteIds);
		$maxRetry = 4; $timeout = 5; $retry = 0;

        try {
			retry:

			/* Se eliminan de tabla producto */
			$table = $this->_resource->getTableName(self::PRODUCT_ENTITY_TABLE);
        	$queryStr1 = 'DELETE FROM '.$table.' WHERE entity_id in('.$deleteIds.')';
			$this->_syncLogQuery->info('QUERY: '.$queryStr1);
            $result = $connection->query($queryStr1);

			/* Se eliminan URL Rewrite */
			$table = $this->_resource->getTableName(self::URL_REWRITE);
            $queryStr2 = 'DELETE FROM '.$table.' WHERE entity_id in('.$deleteIds.') AND entity_type = "product"';
			$this->_syncLogQuery->info('QUERY: '.$queryStr2);
            $connection->query($queryStr2);
		
            return $result->rowCount();

		} catch (\Magento\Framework\DB\Adapter\DeadlockException $e) {
			$retry++;
			if( $maxRetry >= $retry ) {
				echo 'Retry process query '.$retry.PHP_EOL;
				sleep($timeout);
				goto retry;
			} else {
				echo 'ERROR - DEADLOCK EXCEPTION: '.$e->getMessage().PHP_EOL;
				$this->_syncLogger->error($e->getMessage().' Query String: '.$queryStr1.' - '.$queryStr2);
				exit;
			}
		}

	}

	/*
	*	Inserta o Actualiza atributos de productos
	*	
	*	@param $data arreglo campo - valor de atributos a actualizar
	*	@param $values arreglo de campos que seran actualizados en caso de ONDUPLICATE
	*	@param Connection $connection Objeto Conexion (opcional)
	*	@return rows affected
	*/
	public function updateMassProductAttributes($data = array(), $values = array(), $connection = false ){
		$arrayUpdate = $data;
		$total = 0;

		if(!$connection) {
			$connection = $this->_resource->getConnection();
		}

		$table = $this->_resource->getTableName(self::PRODUCT_ENTITY_TABLE);

		/* 
		* Se verifican los arreglos y si tienen registros por procesar se envian a la DB
		* Se utiliza el primer indice del array para saber a que tabla debe ir y con ese valor se obtienen los datos a insertar
		*/
		foreach($arrayUpdate as $key => $val) { 

			if(count($arrayUpdate[$key]) > 0) {
				$result = $connection->insertOnDuplicate(	$table.'_'.$key , $arrayUpdate[$key] , $values );	
			} 
			$total = $total + $result;
		}
		unset($arrayUpdate);
		if( $total > 0 ){
			return $total;
		} else {
			return false;
		}
		
	}

	/*
	*	Carga en array informacion sobre atributos de productos para su uso posterior. 
	*	
	*	Todos los atributos que se deseen procesar en el insert o update deben indicarse aqui
	*
	*	@return void
	*/
	private function getAttributeData(){

		// Se precargan valores de los atributos de productos usados en Update
		$attributes = array (
								'name',		
								'description',	
								'url_key',		
								'product_sku',
								'upc',		
								'manufacturer',
								'manufacturer_sku',
								'price',		
								'special_price',		
								'seller_id',
								'seller_name',		
								'category_origin',
								'shipping_cost',
								'buy_url',
								'image_external_url',
								'status',				
								'visibility',
								'quantity_and_stock_status',
								'tax_class_id',
								'meta_keyword',
								'meta_title',
								'meta_description',
								'options_container',
								'gift_message_available',
								'special_from_date'
							);

		foreach ($attributes as $attribute) {
			$details = $this->_attributeRepository->get($attribute);

			$this->_attributeInfo[$attribute] = array(
					'attribute_id' 		=> $details->getData('attribute_id'),
					'suffix'	 		=> $details->getData('backend_type'),
					'frontend_label' 	=> $details->getData('frontend_label')
				); 
		}

	}	

	public function getProductBySku($sku){
		try {
		    $product = $this->_productRepository->get($sku);
		} catch (\Magento\Framework\Exception\NoSuchEntityException $e){
		    $product = false;
		}
		return $product;
	}

}