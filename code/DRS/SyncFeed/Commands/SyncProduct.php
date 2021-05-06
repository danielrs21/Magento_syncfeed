<?php
/**
* Comando CLI para la sincronización de productos desde Feed (Cache)
* Desarrollado por: Daniel Rodríguez para DRS
* Junio de 2018
*/
namespace DRS\SyncFeed\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;

class SyncProduct extends Command
{
	const DEFAULT_ROOT_CATEGORY = 2;

    /**
     * @var State $state
     */
	protected $_state;

	/** 
	 * @var Item Type $itemType
	 */
	protected $_itemType;

	/** 
	 * @var Sync Mode $syncMode
	 */
	protected $_syncMode;

	/**
	 * @var Service Group $_serviceGroup
	 */
	protected $_serviceGroup;

	/** 
	 * @var Image Sync Mode $imageSyncMode
	 */
	protected $_imageSyncMode;

	/** 
	 * @var Run Indexers $_runIndexers
	 */
	protected $_runIndexers;

	/** 
	 * @var CacheRead $cacheRead
	 */
	protected $_cacheRead;

	/** 
	 * @var AttributeCheck $attributeCheck
	 */
	protected $_attributeCheck;

	/** 
	 * @var ProductRepository $productRepository
	 */
	protected $_productRepository;

	/** 
	 * @var ProductInterfaceFactory $productInterfaceFactory
	 */
	protected $_productInterfaceFactory;

	/** 
	 * @var SyncLogger $syncLogger
	 */
	protected $_syncLogger;

	/** 
	 * @var Indexer Factory $indexerFactory
	 */
	protected $_indexerFactory;

	/** 
	 * @var Indexer Collection Factory $indexerCollectionFactory
	 */
	protected $_indexerCollectionFactory;

	/** 
	 * @var Product Collection Factory $productCollection
	 */
	protected $_productCollection;

	/** 
	 * @var Attribute Collecion $_attributeRepository
	 */
	protected $_attributeRepository;

	/** 
	 * @var Attribute Info $_attributeInfo
	 */
	protected $_attributeInfo;

	/** 
	 * @var Array Update $_arrayUpdate
	 */
	protected $_arrayUpdate = array();

	/** 
	 * @var Delete Ids $_deleteIds
	 */
	protected $_deleteIds = array();

	/**
	 * @var DeleteCacheIds $_deleteIdsCache
	 */
	protected $_deleteIdsCache = array();

	/** 
	 * @var Disable Ids $_deleteIds
	 */
	protected $_disableIds = array();

	/** 
	 * @var Product Storage $_productStorage
	 */
	protected $_productStorage;

	/** 
	 * @var Import Image Service $_importImageService
	 */
	protected $_importImageService;

	/** 
	 * @var Attribute Set Id $_attributeSetId
	 */
	protected $_attributeSetId = array();

	/** 
	 * @var Product Model $_productModel
	 */
	protected $_productFactory;

	/** 
	 * @var ArraySetUpdated Model $_arraySetUpdated
	*/
	protected $_arraySetUpdated = array();

	/** 
	 * @var ArrayUpdateSetCategory Model $_arrayUpdateSetCategory
	*/
	protected $_arrayUpdateSetCategory = array();

	/** 
	 * @var ArrayDeleteSetCategory Model $_arrayDeleteSetCategory
	*/
	protected $_arrayDeleteSetCategory = array();

	protected $_categoryList;
    /**
     * Constructor
     *
     * @return void
     */
	public function __construct(
			\Magento\Framework\App\State $state, 
			\Magento\Framework\Registry $registry, 
			\Magento\Catalog\Api\Data\ProductInterfaceFactory $productFactory,
			//\Magento\Catalog\Model\ProductFactory $productFactory,
			\Magento\Catalog\Model\ProductRepository $productRepository,
			\Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository, 
			\Magento\Indexer\Model\IndexerFactory $indexerFactory, 
			\Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory,
			\DRS\SyncFeed\Logger\SyncLogger $syncLogger, 
			\DRS\SyncFeed\Helper\AttributeCheck $attributeCheck, 
			\DRS\SyncFeed\Service\ImportImageService $importimageservice, 
			\DRS\SyncFeed\Model\CacheRead $cacheRead, 
			\DRS\SyncFeed\Model\ProductStorage $productStorage,
			\DRS\SyncFeed\Model\Config\Source\Categorylist $categoryList
		){
		$this->_state 					 = $state;
		$this->_productFactory			 = $productFactory;
		$this->_productRepository 		 = $productRepository;
		$this->_attributeRepository 	 = $attributeRepository;
		$this->_indexerFactory 			 = $indexerFactory;
		$this->_indexerCollectionFactory = $indexerCollectionFactory;
		$this->_syncLogger 				 = $syncLogger;				
		$this->_attributeCheck 			 = $attributeCheck;
		$this->_importimageservice 		 = $importimageservice;
		$this->_cacheRead 				 = $cacheRead;		
		$this->_productStorage 			 = $productStorage;
		$this->_categoryList			 = $categoryList;

		/* Se definen los valores default de las opciones de ejecución. */
		$this->_syncMode 		= 'all';
		$this->_imageSyncMode 	= 'no';
		$this->_runIndexers 	= false;

		/* Obtener atribute set del modulo */
		$this->_attributeSetId[1] = $this->_attributeCheck->getAttributeSet('Affiliate');
		$this->_attributeSetId[2] = $this->_attributeCheck->getAttributeSet('Coupons');
		$this->_attributeSetId[3] = $this->_attributeCheck->getAttributeSet('Resolt');

		$registry->register('isSecureArea', true);
		ini_set("memory_limit","4095M");
		ini_set('zend.enable_gc', 1);
		parent::__construct();
	}

    /**
     * Configures arguments and display options for this command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('syncfeed:sync:products');
        $this->setDescription('DRS - Sync Feed - Add or Update Products from Feed');
        $this->addArgument('ItemType', InputArgument::REQUIRED, 'Type of product to Sync: 
        									affiliate = Sync only affiliate products from cache.
        									coupons = Sync only coupons items from cache.
        									resold = Sync only resold product from cache.
        									all = Sync all items from cache.');

        $this->addOption('SyncMode', 's', InputOption::VALUE_OPTIONAL, "Sync Mode: 
											add = Only add new products.
											update = Only update existing products.
											<comment>all = Add new and update existing products. (default)</comment>");

        $this->addOption('ServiceGroup', 'g', InputOption::VALUE_OPTIONAL, "Service Group:
        									specify the service group to process"); 

        $this->addOption('ImageSyncMode', 'i',InputOption::VALUE_OPTIONAL, "Image Sync Mode: 
											inline = Load image together with product registration. 
											after  = Load image after products sync.
											<comment>no  = No load image. (default)</comment>
											");

        $this->addOption('RunIndexers', 'r',InputOption::VALUE_OPTIONAL, "Run Indexers after sync: 
											yes = Run indexer:reindex after ends sync products. 
											<comment>no  = No run indexer:reindex. (default)</comment>
											");

        parent::configure();
    }


    /**
     * Lee el feed y ejecuta acciones de crear o actualizar segun corresponda.
     *
     * @param Input $input InputInterface
     * @param output  $output  OutputInterface
     *
     * @return void
     */
	protected function execute(InputInterface $input, OutputInterface $output){
        try {
            $this->_state->setAreaCode('adminhtml');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Intentionally left empty.
        }

        // Se evalua el argumento principal
		$this->_itemType = strtolower($input->getArgument('ItemType'));
		$content_options = array('affiliate','coupons','resold','all');
		if( !in_array( $this->_itemType, $content_options ) ){
			$output->writeln('<error>Invalid item type: "'.$this->_itemType.'". Options available: affiliate, coupons, resold, all.</error>');
			exit;			
		}

        // Se evalua el parámetro SyncMode 
		if ($input->getOption('SyncMode')) {
			$syncModeVal = strtolower($input->getOption('SyncMode'));
			$content_options = array('add','update','all');
			if( in_array($syncModeVal,$content_options) ){
				$this->_syncMode = $syncModeVal;
			} else {
				$output->writeln('<error>Invalid parameter sync products mode: "'.$syncModeVal.'"</error>');
				exit;
			}
		} 

        // Se evalua el parámetro ServiceGroup 
		if ($input->getOption('ServiceGroup')) {
			$this->_serviceGroup = strtolower($input->getOption('ServiceGroup'));
		}
		
		// Se evalua parámetro ImageSyncMode
		if ($input->getOption('ImageSyncMode')) {
			$imageSyncModeVal = strtolower($input->getOption('ImageSyncMode'));
			$content_options = array('inline','after','no');
			if( in_array($imageSyncModeVal,$content_options) ){
				$this->_imageSyncMode = $imageSyncModeVal;
			} else {
				$output->writeln('<error>Invalid parameter image sync mode: "'.$imageSyncModeVal.'"</error>');
				exit;
			}
		} 

		// Se evalua parámetro RunIndexers
		if ($input->getOption('RunIndexers')) {
			$runReindexVal = strtolower($input->getOption('RunIndexers'));
			$content_options = array('yes','no');
			if( in_array($runReindexVal,$content_options) ){
				$this->_runIndexers = True;
			} else {
				$output->writeln('<error>Invalid parameter run indexers mode: "'.$runReindexVal.'"</error>');
				exit;
			}
		} 

		/* Obtiene valores de atributos para actualización de productos */
		$this->getAttributeData($output);

        /* Muestra mensaje cabecera */
        $this->renderHeader($output);

        /* Ejecuta sincronización */
		$this->syncProducts($output);

		/* Si es indicado se ejecuta el reindex magento */
		if($this->_runIndexers) {
			$this->runIndexers($output);
		}

		/* Si es indicado se ejecuta la actualización de imagenes */
		if($this->_imageSyncMode=='after'){
			$this->_importimageservice->execute( $output , true );
		}

	}

	private function renderHeader($output){
        $table = new Table($output);
        $table
        	->setHeaders(array('<comment>DRS - SyncFeed Module </comment>'))
        	->setRows(array(
                array('Item type to sync: '.$this->_itemType),
                array('Sync mode: '.$this->_syncMode),
                array('Image sync mode: '.$this->_imageSyncMode),
                array('Run Indexers after: '.$this->_runIndexers),
            ))
        ;
        $table->render();
	}

    /**
     * Lee la cache en busca de productos y ejecuta acciones de crear o actualizar segun corresponda.
     *
     * @param output  $output  OutputInterface
     *
     * @return void
     */
	private function syncProducts($output){
		$this->_syncLogger->info('Iniciando proceso de sincronizacion...');
		// Filtros
		switch ($this->_syncMode) {
			case 'add':
				$filters = ' sync_new = 1 AND item_status = 1 ';
				break;
			case 'update':
				$filters = ' sync_new = 0 '; 
				break;
			default:
				$filters = false;
				break;
		}
		if($this->_serviceGroup) {
			if($filters) {
				$filters.= ' AND service_group = "'.$this->_serviceGroup.'" '; 
			} else {
				$filters = ' service_group = "'.$this->_serviceGroup.'" '; 
			}
		}
		switch ($this->_itemType) {
			case 'affiliate':
				if($filters){
					$filters.= ' AND item_type_id = 1';
				} else {
					$filters = ' item_type_id = 1';
				}
				break;
			case 'coupons':
				if($filters){
					$filters.= ' AND item_type_id = 2 ';
				} else {
					$filters = ' item_type_id = 2 ';
				}
				break;
			case 'resold':
				if($filters){
					$filters.= ' AND item_type_id = 3 ';
				} else {
					$filters = ' item_type_id = 3 ';
				}
				break;
		}

		// Se obtiene el total de registros existentes en cache
		$records = $this->_cacheRead->getcount($filters);

		// Se verifica que no venga vacio el array o no existan productos en cache
		if ( $records == 0 ) {
			$this->_syncLogger->info('No se encontraron productos para crear o actualizar');
			$output->writeln('<error>No products information found.</error>');
			return;
		}

		$limit 		= 5000;									// Limite de registros a obtener por cada peticion
		$offset 	= 0;									// Valor inicial del offset de registros
		$totalpages = (int) ($records / $limit) + 1;		// Paginas totales calculadas en base a los registros obtenidos
		$page 		= 0;									// Pagina inicial

		$output->writeln('<info>Se procesaran '.$records.' registros en cache.<info>');
		$this->_syncLogger->info('Se procesaran '.$records.' registros en cache.');

		$start_time = date("Y-m-d H:i:s");
		$start = microtime(true);

//		$progressBar = new ProgressBar($output, $records);
//		$progressBar->start();
	//	$progressBar->setRedrawFrequency(20);

		$error 	 = 0;	// Contador de errores 
		$created = 0;	// Contador de items creados
		$updated = 0;	// Contador de items actualizados

		do {
			// Se calcula el offset en base a la pagina actual
			$offset = $page * $limit;
			
			// Se obtienen los items de la cache
			$items = null;
			unset($items);
			
			if($this->_syncMode == 'add') {
				$items = $this->_cacheRead->getItems($limit, 0, $filters);
			} else {
				$items = $this->_cacheRead->getItems($limit, $offset, $filters);
			}
			
			// Se procesan los items
			foreach ($items as $item) {
				/* Codigo unico para identificar producto tanto en cache como en magento */ 
				$product_sku = $item->item_code;

				/* Verifica si el producto existe en magento */
				$product = $this->getProductBySku($product_sku);
				if($product){
					if($this->_syncMode == 'update' || $this->_syncMode == 'all') {
						$result = $this->updateProduct($item,$product);
						if($result){
							$cont = $result; 
							@$$cont++;
								
						}
					}
				} elseif($this->_syncMode == 'add' || $this->_syncMode == 'all') {
					/* SOLO SE INSERTA SI EL PRODUCTO ESTA ACTIVO EN CACHE Y MARCADO PARA INSERTAR */
					if($item->item_status == 1 && $item->sync_new == 1){
						$loadImage = ( $this->_imageSyncMode == 'inline' );
						$result = $this->_productStorage->createProduct($item, $loadImage);
						$cont = $result; 
						@$$cont++;	
					} elseif($item->item_status == 0) {
						// Eliminar de la cache por estar desactivado y no existir en magento 
						$this->_deleteIdsCache[] = $item->item_id;						
					}
				} elseif($item->item_status == 0){
					// Eliminar de la cache por estar desactivado y no existir en magento 
					$this->_deleteIdsCache[] = $item->item_id;
				}
			//	$progressBar->advance();
			//	echo 'Create: '.$created.' Update: '.$updated. 'Delete: '.count($this->_deleteIds);
				$this->runUpdateMass(1000);
				$product = null;
			}

			$page++;

		} while ($page < $totalpages);

		/* SE ENVIAN A DB LOS QUERYSTRING UPDATES REMANENTES DE PROCESAR  */
		$this->runUpdateMass();

		/* SE IMPRIMEN VALORES DE RESULTADOS DEL PROCESO */
//		$progressBar->finish();
		$output->writeln(' ');
		$end_time =  date("Y-m-d H:i:s");
		$date1 = new \DateTime($start_time); $date2 = new \DateTime($end_time);
		$total_time = $date1->diff($date2);
		$textOutput1 = $created.' products created, '.$updated.' products updated, '.@$deleted.' deleted or disabled, '.$error.' errors.';
		$textOutput2 = 'Sync completed in '.$total_time->format("%H:%I:%S");
		$output->writeln($textOutput1); $this->_syncLogger->info($textOutput1);
		$output->writeln($textOutput2); $this->_syncLogger->info($textOutput2);	
	}

	private function runUpdateMass($limit = 0){
		/* Actualiza atributos */
		if(count($this->_arrayUpdate, COUNT_RECURSIVE) > $limit){
			$this->_productStorage->updateMassProductAttributes( $this->_arrayUpdate, array('value') );
			unset($this->_arrayUpdate);
			$this->_arrayUpdate = array();
		}
		/* Actualiza categorias */
		if(count($this->_arrayUpdateSetCategory) > $limit) {
			$this->_productStorage->updateMassProductInCategory( $this->_arrayUpdateSetCategory );
			unset($this->_arrayUpdateSetCategory);
			$this->_arrayUpdateSetCategory = array();
		}
		/* Elimina categorias ya no asignadas */ 
		if(count($this->_arrayDeleteSetCategory) > $limit) {
			$this->_productStorage->deleteMassProductInCategory( $this->_arrayDeleteSetCategory );
			unset($this->_arrayDeleteSetCategory);		
			$this->_arrayDeleteSetCategory = array();
		}
		/* Actualiza id magento en item cache */ 
		if(count($this->_arraySetUpdated) > $limit) {
			$this->_cacheRead->setUpdated( $this->_arraySetUpdated );
			unset($this->_arraySetUpdated);
			$this->_arraySetUpdated = array();
		}
		/* Elimina productos desactivados en cache - solo afiliacion */
		if(count($this->_deleteIds) > $limit ){
			$this->_productStorage->deleteMassProducts( $this->_deleteIds );
			unset($this->_deleteIds);
			$this->_deleteIds = array();
		}

		/* Elimina productos desactivados de la cache - solo afiliacion */
		if(count($this->_deleteIdsCache) > $limit ){
			$this->_cacheRead->deleteMassProducts( $this->_deleteIdsCache );
			unset($this->_deleteIdsCache);
			$this->_deleteIdsCache = array();
		}
	}

    /**
     * Actualiza producto en Magento
     *
     * @param item  $item  Datos del producto desde el feed
     * @param product_id $product_id Id de producto para actualizar
     *
     * @return string ('updated' or 'error')
     */
	private function updateProduct($item,$product){

		try 
		{
			// Si el item esta activo en cache se procesa, sino se elimina de magento. 
			if($item->item_status == 1){

				// Si se indica el parametro imageSync = inline se carga la imagen al producto
				if($this->_imageSyncMode == 'inline'){
					if($product->getImage() == NULL or $product->getImage() == 'no_selection') {
						$this->_importimageservice->loadImageToProduct(
							$product, 
							false,
							array('image', 'small_image', 'thumbnail'),
							true
						);
					}
				}

				$entityId = $product->getEntityId();

				if( $item->external_category == 0 ){
				//if( $item->item_type_id == 3 ){
					
					$categoryCache = array();

					$categoryMagento = $product->getCategoryIds();

					$categories = explode(",",trim($item->category));

					/* Se buscan las categorias magento y se asigna el id al array */
					foreach($categories as $cat){
		
						$cats = explode('/',trim($cat));
		
						if( count($cats) > 1 ) {
							$categoryCache[] = $this->_categoryList->getIdByName($cats);
						} 
		
					}

					/* Si no hay categorias se asigna la raiz por defecto */
					if( count( $categoryCache ) == 0 ) {
						$categoryCache = array(self::DEFAULT_ROOT_CATEGORY);
					}

					$categoryCache = array_unique($categoryCache);

					/* Verificar categorias aun no asignadas al producto */
					foreach( array_diff($categoryCache,$categoryMagento) as $category ){
						if( $category !== self::DEFAULT_ROOT_CATEGORY ){
							$this->_arrayUpdateSetCategory[] = [
								'category_id' 	=> $category,
								'product_id' 	=> $entityId,
								'position'	 	=> 0 // Default
							];
						}
					}

					/* Verificar categorias magento que ya no estan asignadas en cache */
					foreach( array_diff($categoryMagento,$categoryCache) as $category ){
						$this->_arrayDeleteSetCategory[] = [ 
							'product_id' => $entityId, 
							'category_id' => $category 
						];
					}

				}
				
				// Los dos arreglos siguientes deben ser identicos en cuanto a cantidad y nombres de keys

				// Arreglo de campos de producto a verificar cambios
				$productData = array (
					'price'				=> $product->getPrice(),
					'special_price' 	=> $product->getSpecialPrice(),
					'buy_url' 			=> $product->getBuyUrl(),
					'image_external_url'=> $product->getImageExternalUrl(),
					'seller_name'		=> $product->getSellerName(),
					'product_sku'		=> $product->getProductSku(),
					'shipping_cost'		=> $product->getShippingCost(),
					'description'		=> $product->getDescription(),
					'manufacturer'		=> $product->getManufacturer()
				);

				// Arreglo de campos de cache a comparar
				$cacheItem = array (
					'price'				=> $item->item_last_normal_price,
					'special_price'		=> $item->item_last_price,
					'buy_url'			=> trim($item->item_buy_url),
					'image_external_url'=> trim($item->item_image_url),
					'seller_name'		=> trim($item->store_name),
					'product_sku'		=> trim($item->item_sku),
					'shipping_cost' 	=> $item->shipping_cost,
					'description'		=> trim(stripcslashes($item->item_description))
				);

				if(trim($item->manufacturer_name)) {
					$cacheItem['manufacturer'] = $this->_attributeCheck->createOrGetId('manufacturer', strtoupper( trim( str_replace('"','',$item->manufacturer_name) ) ) );
				} else {
					$cacheItem['manufacturer'] = null;
				}
				
				if($item->item_type_id !== 3 ) { 
					unset($cacheItem['shipping_cost']);
					unset($productData['shipping_cost']);
				}

				$updateItem = false;

				// Se verifican los valores existentes contra la cache, si hay cambios se agregan al batch para actualizar
				foreach ($productData as $attribute => $value) {
					
					if($value !== $cacheItem[$attribute]) {
				
						// Se agrega al arreglo de updates en batch 
						$suffix = $this->_attributeInfo[$attribute]['suffix'];

						$this->_arrayUpdate[$suffix][] = array (
							'attribute_id'  => 	$this->_attributeInfo[$attribute]['attribute_id'],
							'store_id'      => 	0,
							'entity_id'     => 	$entityId,
							'value'         => 	$cacheItem[$attribute]
						);
						$updateItem = true;
					}
				}

				if($product->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED ){
					/* Se actualiza el estatus del producto dado que puede haber sido desactivado en una sincronización previa */
					$suffix = $this->_attributeInfo['status']['suffix'];
					$this->_arrayUpdate[$suffix][] = array (
						'attribute_id'  => 	$this->_attributeInfo['status']['attribute_id'],
						'store_id'      => 	0,
						'entity_id'     => 	$entityId,
						'value'         => 	\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
					);
					$updateItem = true;
				}

				if($updateItem) {
					$this->_arraySetUpdated[] = [ 'item_id' => $item->item_id, 'entity_id' => $entityId ];
					$this->_syncLogger->info('Producto actualizado: '.$entityId);
					return "updated";
				} else {
					return false;
				}
			} else {
				// El item ha sido desactivado en la cache, se inactiva o elimina de magento.
				switch ($item->item_type_id){
					case 1:
					case 2:
						$this->_deleteIds[] = $product->getEntityId();
						$this->_deleteIdsCache[] = $item->item_id;
						break;
					case 3:
						if($product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED ){ 
							$suffix = $this->_attributeInfo['status']['suffix'];
							$this->_arrayUpdate[$suffix][] = array (
								'attribute_id'  => 	$this->_attributeInfo['status']['attribute_id'],
								'store_id'      => 	0,
								'entity_id'     => 	$product->getEntityId(),
								'value'         => 	\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED
							);
						}
						break;
				}
				return "deleted";
			}
		}
		catch (Exception $e)
		{
			$this->_syncLogger->error($e->getMessage());
			return "error";
		}

	}

    /**
     * Obtiene datos de un producto en Magento
     *
     * @param sku  $sku  Código SKU del producto a buscar
     *
     * @return array or false
     */
	private function getProductBySku($sku){
		try {
		    $product = $this->_productRepository->get($sku);
		} catch (\Magento\Framework\Exception\NoSuchEntityException $e){
		    $product = false;
		}
		return $product;
	}

    /**
     * Obtiene Datos de Attributos de Producto
     *
     * @return void
     */
	public function getAttributeData($output){

		// Se precargan valores de los atributos de productos usados en Update
		$attributes = array (
								'description',
								'category_origin',
								'buy_url',
								'image_external_url',
								'price',
								'special_price',
								'category_origin',
								'seller_name',
								'product_sku',
								'shipping_cost',
								'status',
								'manufacturer'
							);

		foreach ($attributes as $attribute) {
			$details = $this->_attributeRepository->get($attribute);

			$this->_attributeInfo[$attribute] = array(
					'attribute_id' 		=> $details->getData('attribute_id'),
					'suffix'	 		=> $details->getData('backend_type'),
					'frontend_label' 	=> $details->getData('frontend_label'),
					'frontend_input'	=> $details->getData('frontend_input')	
				); 
		}

	}

    /**
     * Realiza indexado de productos
     *
     * @return void
     */
	private function runIndexers(){
		echo 'Running indexers... '.PHP_EOL;
	    $indexer = $this->_indexerFactory->create();
	    $indexerCollection = $this->_indexerCollectionFactory->create();
	    $ids = $indexerCollection->getAllIds();
	    foreach ($ids as $id){
	        $idx = $indexer->load($id);
        	echo $id.PHP_EOL;
            $idx->reindexRow($id);
	    }
	}

	/***********************************************************************************/
	/* FUNCIONES DE APOYO - HERRAMIENTAS PUNTUALES - NO TIENEN RELACIÓN CON EL COMANDO */
	/***********************************************************************************/
	private function showFiles($path){
	    $dir = opendir($path);
	    $files = array();
	    $valid = 0;
	    while ($current = readdir($dir)){
	        if( $current != "." && $current != "..") {
	            if(is_dir($path.$current)) {
	                $this->_showFiles($path.$current.'/');
	            }
	            else {
	            	$result = $this->_importimageservice->is_image($path.$current);
	            	if($result){
	            		$valid++;
	            	} else {
	            		$files[] = $path.$current;
	            		echo "INVALID: ".$path.$current;
	            		if(unlink($path.$current)){
	            			echo "DELETED".PHP_EOL;
	            		} else {
	            		 	echo "UNABLE DELETE".PHP_EOL;
	            		}
	            	}
	            }
	        }
	    }
	}

	public function deleteImgProduct(){

		$collection = $this->_productCollection->create();
		$collection->addAttributeToSelect(array('seller_id','entity_id'));
		$collection->addAttributeToFilter('seller_id','4019306');
		$productIds = array();
		foreach($collection as $_product){
			$productIds[] = $_product->getEntityId();
		}
		$string = '';
		foreach ($productIds as $id) {
			$string.= $id.',';
		}
		
		echo PHP_EOL.$string.PHP_EOL;
		echo "listo: ".count($productIds);
	}

}