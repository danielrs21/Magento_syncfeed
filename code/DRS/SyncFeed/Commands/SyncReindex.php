<?php
/**
* Comando CLI para la reasignación de categorias a productos asignadas en Category Match
* Desarrollado por: Daniel Rodríguez
* Julio de 2018
*/

namespace DRS\SyncFeed\Commands;

// Symfony Objects
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;

class SyncReindex extends Command
{
	const TABLE_CATEGORY_PRODUCT = 'sif_catalog_category_product';
	const TABLE_CATEGORYMATCH	 = 'sif_drs_syncfeed_categorymatch';

    /**
     * @var State $state
     */
	protected $_state;

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
	 * @var Product Collection Factory $_productCollection
	 */
	protected $_productCollection;

	/** 
	 * @var Category List $_categoryList
	 */
	protected $_categoryList;

	/** 
	 * @var Category Product Factory $_categoryProduct
	 */
	protected $_categoryProduct;

	/** 
	 * @var Category Match Factory $_categoryMatch
	 */
	protected $_categoryMatch;

	/** 
	 * @var Insert Batch $_insertBatch
	 */
	protected $_insertBatch = array();

	/** 
	 * @var Delete Ids $_deleteIds
	 */
	protected $_deleteIds = array();

	/** 
	 * @var Resource $_resource
	 */
	protected $_resource;

	/** 
	 * @var AttributeSet $_attributeCollection
	 */
	protected $_attributeSetCollection;

	/** 
	 * @var Attribute $_attribute
	 */
	protected $_attribute;

	/** 
	 * @var AttributeCheck $attributeCheck
	 */
	protected $_attributeCheck;
    /**
     * Constructor
     *
     *
     * @return void
     */
	public function __construct(
			\Magento\Framework\App\State $state,
			\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
			\DRS\SyncFeed\Logger\SyncLogger $syncLogger,
			\Magento\Indexer\Model\IndexerFactory $indexerFactory,
			\Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory,
			\DRS\SyncFeed\Model\CategoryProductFactory $categoryProduct,
			\DRS\SyncFeed\Model\CategoryMatchFactory $categoryMatch,
			\DRS\SyncFeed\Model\Config\Source\Categorylist $categoryList,
			\Magento\Framework\App\ResourceConnection $resource,
			\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attributeSetCollection,
			\DRS\SyncFeed\Helper\AttributeCheck $attributeCheck
		){
		$this->_state = $state;
		$this->_productCollection = $productCollection; 
		$this->_syncLogger = $syncLogger; 
		$this->_indexerFactory = $indexerFactory; 
		$this->_indexerCollectionFactory = $indexerCollectionFactory; 
		$this->_categoryProduct = $categoryProduct; 
		$this->_categoryMatch = $categoryMatch; 
		$this->_categoryList = $categoryList; 
		$this->_resource = $resource;
		$this->_attributeSetCollection = $attributeSetCollection;
		$this->_attributeCheck = $attributeCheck;
		parent::__construct();
	}

    /**
     * Configures arguments and display options for this command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('syncfeed:category:reindex');
        $this->setDescription('DRS - Sync Feed - Reindex Match Category');
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

        // Muestra mensaje cabecera
        $this->renderHeader($output);
        // Ejecuta el reindexado de Category Match
		$this->reindexCategory($output);
		// Ejecuta el Reindex de Magento para actualizar productos en Catalogos
		$this->runIndexers($output);
	}

    /**
     * Muestra cabecera en Consola
     *
     * @param output  $output  OutputInterface
     *
     * @return void
     */
	private function renderHeader($output){
        $table = new Table($output);
        $table
        	->setHeaders(array('<comment>DRS - SyncFeed Module</comment>'))
        	->setRows(array(
                array('Category Match Reindex'),
            ))
        ;
        $table->render();
	}

    /**
     * Realiza el reindexado de Category Match 
     *
     * @param output  $output  OutputInterface
     *
     * @return void
     */
	private function reindexCategory($output){
		$start_time = date("Y-m-d H:i:s");
		$start = microtime(true);

		$output->writeln('Loading product collection...');

		// Obtener atribute set de productos feed
		$attributeSetId = $this->_attributeCheck->getAttributeSet('Affiliate');
        
		// Obtener productos magento con Set Attribute Affiliate. 
		$collection = $this->_productCollection->create();
		$collection->addAttributeToSelect('category_origin');
		$collection->addFieldToFilter('attribute_set_id',$attributeSetId);

		// Contar los productos registrados
		$count = count( $collection->getItems() );
		//$output->writeln("Products to reindex: ".$count);

		// Obtener Ids de categorias magento
		$output->writeln('Loading category collection...');
		$this->_syncLogger->info('Loading category collection...');
		$catIds = $this->_categoryList->getAllIds();

	    $this->_syncLogger->info( 'Se verificaran categorias de '.$count.' productos' );

		$progressBar = new ProgressBar($output, $count);
		$progressBar->start();
		$progressBar->setRedrawFrequency(100);

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

	    		$category_ids = explode(',', $category_ids);

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
									$this->_deleteIds[] = $item->getEntityId();
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
								$this->_syncLogger->info('The category_feed: '.$_product->getData('category_origin').' associated Magento Category Id:'.$newCat.' not exist.');
								$categoryNotExist++;
							}	
		    			}				

					} catch (Exception $e) {
						$this->_syncLogger->info($e->getMessage());
						$error++;
					}
		    	}
	    	} else {
	    		$notLinked++;
				// Eliminar asociaciones actuales ya que en Category Match no existe ninguna asociación.
				foreach ($categoryProduct as $item) {
					$this->_deleteIds[] = $item->getEntityId();
					$deleted++;
				}

	    	}

	    	$progressBar->advance();

 	    	// OPCIONAL: Se seccionan las peticiones batch a un limite de registros 
	    	if(count($this->_deleteIds) >= 5000 ) {
	    		$this->sendDelete();
	    	}
	    	if(count($this->_insertBatch) >= 5000 ) {
	    		$this->sendData();
	    	}
	    }

	    $progressBar->finish();

	    // Se envian los datos pendientes por almacenar en DB
    	if(count($this->_deleteIds)>0) {
    		$this->sendDelete();
    	}
	    if(count($this->_insertBatch)>0)
	    {
			$this->sendData();
		}

	    $output->writeln(' ');
	    $output->writeln($updated.' Products Linked to Category');
	    $output->writeln($equal.' Products not requires indexing');
	    $output->writeln($deleted.' Products Link deleted');
	    $output->writeln($categoryNotExist.' Products Link to invalid Magento Category');
	    $output->writeln($error.' Errors in assign category');
	    $output->writeln($notLinked.' Products without Category Match');
	    $output->writeln($count.' Total products verify');

	    if( $error > 0 ){
	    	$output->writeln('<error>Errors ocurred during the process, verify log file in var/log/syncfeed.log for details.</error>');
	    }

	    if( $categoryNotExist > 0 ){
	    	$output->writeln('<comment>There are category match associated with magento categories that no longer exist, verify log file in var/log/syncfeed.log for details.</comment>');
	    }

	    $this->_syncLogger->info('CategoryMatch Reindexer Finished');
	    $output->writeln("Process duration: ".(microtime(true) - $start)." seconds");

	}

    /**
     * Agrega registro para insert en arreglo Batch
     *
     * @param $category_id Category Id
     * @param $product_id Produc Id
     * @param $position Position     
     *
     * @return void
     */
	private function addInsert( $category_id, $product_id, $position )
	{
		$this->_insertBatch[] = [
					        		'category_id' 	=> $category_id,
					        		'product_id' 	=> $product_id,
					        		'position'	 	=> $position
    							];
	}

    /**
     * Envia query para borrar asociación a categorias de productos
     *
     * @return void
     */
	private function sendDelete(){
		$connection = $this->_resource->getConnection();
		$deleteIds = implode(',',$this->_deleteIds);
        try {
        	$table = $connection->getTableName(self::TABLE_CATEGORY_PRODUCT);

            $connection->query('DELETE FROM '.$table.' WHERE entity_id in('.$deleteIds.')');
	        unset($this->_deleteIds); $deleteIds = '';
			$this->_deleteIds = array();

		} catch (\Exception $e) {
			$this->_syncLogger->info($e);
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

        	$table = $connection->getTableName(self::TABLE_CATEGORY_PRODUCT);

            $connection->insertMultiple($table, $this->_insertBatch);
	        // Se limpia el array para no volver a insertar los mismos registros en otra petición.
	        unset($this->_insertBatch);
			$this->_insertBatch = array();

        } catch (\Exception $e) {

            if ($e->getCode() === 23000
                && preg_match('#SQLSTATE\[23000\]: [^:]+: 1062[^\d]#', $e->getMessage())
            ) {
                throw new \Magento\Framework\Exception\AlreadyExistsException(
                    __('URL key for specified store already exists.')
                );
            }
           	throw $e;
           	$this->_syncLogger->info($e);
        }
	}

    /**
     * Devuelve Ids de categorias asociadas en Category Match
     *
     * @param $categoryOrigin Valor de Categoria de Origen del producto a buscar
     *
     * @return CategoryIds String or False
     */
	private function getCategoryIds($categoryOrigin)
	{
		$connection = $this->_resource->getConnection();

		$table = $connection->getTableName(self::TABLE_CATEGORYMATCH);

		$sql = ' 
			SELECT 
				category_match 
			FROM 
				'.$table.' 
			WHERE 
				category_feed = "'.$categoryOrigin.'"';

        $result = $connection->fetchAll($sql); 
        if($result){
        	return $result{0}['category_match'];
        } else {
        	return false;
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
	            $idx->reindexAll($id);
	    }

	}

}