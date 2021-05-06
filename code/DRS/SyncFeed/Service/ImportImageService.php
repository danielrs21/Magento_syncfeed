<?php

namespace DRS\SyncFeed\Service;

use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class ImportImageService
 * Search products whitout image and assign it to products by attribute image_external_url value
 */
class ImportImageService
{
    const HEADERS = array (
                        "User-Agent:Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13",
                        "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                        "Accept-Language:en-us,en;q=0.5",
                        "Accept-Encoding:gzip,deflate",
                        "Accept-Charset:ISO-8859-1,utf-8;q=0.7,*;q=0.7",
                        "Cache-Control:max-age=0"
                    );

    //const IMAGE_FORMAT = array ( 'jpeg', 'jpg', 'png', 'gif' );
    const IMAGE_FORMAT = array ( 'jpeg', 'jpg', 'png' );

    //const CONTENT_TYPE_IMAGE = array ( 'image/png' , 'image/jpeg' , 'image/gif', 'JPG', 'PNG', 'jpg', 'png', 'JPEG', 'jpeg', 'GIF', 'gif', 'application/octet-stream' );

    const CONTENT_TYPE_IMAGE = array ( 'image/png' , 'image/jpeg' , 'JPG', 'PNG', 'jpg', 'png', 'JPEG', 'jpeg', 'application/octet-stream' );

    protected $_extension = array (
                                'image/png'  => 'png',
                                'image/jpeg' => 'jpg',
                                'image/gif'  => 'gif',
                                'JPG'        => 'jpg',
                                'jpg'        => 'jpg',
                                'PNG'        => 'png',
                                'png'        => 'png',
                              //  'GIF'        => 'gif',
                              //  'gif'        => 'git',
                                'JPEG'       => 'jpg',
                                'jpeg'       => 'jpg'
                                  );

    /** 
     * @var SyncLogger $syncLogger
     */
    protected $_syncLogger;

    /**
     * @var DirectoryList
     */
    protected $_fileSystem;

    /**
     * @var File
     */
    protected $_file;

    /**
     * @var Product Colletion
     */
    protected $_productCollection;

    /**
     * ImportImageService constructor
     *
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Filesystem\Io\File $file,
        \DRS\SyncFeed\Logger\SyncLogger $syncLogger,  
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
    ) {
        $this->_file = $file;
        $this->_syncLogger = $syncLogger;
        $this->_productCollection = $productCollection;
        $this->_mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('tmp');
    }

    /**
     * ImportImageService Executor Process
     *
     * @param OutputInterface $output
     * @param Verbose $verbose true for console execution
     */
    public function execute($output, $verbose = false){
        if($verbose) $output->writeln('Searching products whitout images...');

        $this->_syncLogger->info('Searching products whitout images...');

        $j=0;$x=0;
        $page = 1;
        $pages = 1; 

        while ($page <= $pages) {

            if($verbose) $output->writeln('Processing page: '.$page);
            $this->_syncLogger->info('Processing page: '.$page);

            $collection = $this->_productCollection->create();
            $collection->addAttributeToSelect(array('sku','image','url_key','image_external_url','seller_id','buy_url'));
            $collection->addFieldToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED );

            if($page==1){
                $count = $collection->getSize();
                $pages = $count / 1000; 
                if($pages > (int)$pages) $pages = (int)$pages++; 
                $this->_syncLogger->info('Total pages: '.$pages);
            }

            $collection->setPageSize(1000);
            $collection->setCurPage($page);
            $collection->load();

            foreach($collection as $_product){
                
                if($_product->getImage() == NULL or $_product->getImage() == 'no_selection') {
               
                    if($_product->getData('image_external_url') != NULL){
                        $memoryUsageBefore = memory_get_usage();
                
                        $result = $this->loadImageToProduct(
                            $_product, 
                            false,
                            $imageType = ['image', 'small_image', 'thumbnail'],  
                            true
                        );
                
                        $this->_syncLogger->info('Memory usage before: '.$memoryUsageBefore.' after save: '.memory_get_usage().' Variation: '.round((memory_get_usage() - $memoryUsageBefore)/1024,2).' KB' );
                        if( $result ){                      
                            $j++;
                        } else {
                            $x++;
                            /* Desactivar producto */ 
                       //     $_product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
                        //    $_product->save();
                        }
                    } else {
                        $x++;
                    }
                }
            }
            unset($collection);
            unset($_product);
            $page++; 
        }
        
        $textOutput = 'Se han subido imagenes a '.$j.' de '.$i.' productos. '.$x.' no encontrados.';
        if($verbose) $output->writeln($textOutput);
        $this->_syncLogger->info($textOutput);
  
    }

    /**
     * Main service executor
     *
     * @param Product $product
     * @param string $imageUrl
     * @param array $imageType
     * @param bool $visible
     *
     * @return bool
     */
    public function loadImageToProduct($product, $imageUrl = false, $imageType = [], $save=false)
    {
        $result = true;
        if(!$imageUrl){
            $imageUrl = $product->getImageExternalUrl();
        } 
        
        $this->_syncLogger->info($imageUrl);

        // Descarga y guarda la imagen en ruta temporal
        if($product->getBuyUrl()) {      
            $filename = $this->getFileCurl( $imageUrl, $product->getBuyUrl() ); 
        } else {
            return false;
        }

        // Si se logro obtener la imagen se carga en magento
        if ($filename) {
            // Se verifica el contenido del archivo es de imagen
            if( $this->is_image($filename) ){

                $product->addImageToMediaGallery($filename, $imageType, false, false);
                if($save) {
                    $max_tries = 4;
                    $tries = 1;
                    do {
                        $retry = false;
                        try {
                            $product->save();
                        } catch (\Magento\Framework\DB\Adapter\DeadlockException $e) {
                            if ($tries < $max_tries && preg_match('/SQLSTATE\[40001\]/', $e->getMessage())) {
                                $retry = true;
                                $this->_syncLogger->info('Retry '.$tries.' save after lock database '.$product->getEntityId());
                                sleep(60*$tries);
                            } else {
                                throw new Zend_Db_Statement_Exception($e->getMessage(), (int)$e->getCode(), $e);
                            }
                            $tries++;
                        } catch (\PDOException $e){
                            $result = false;                            
                        } catch (\Magento\Framework\Exception\RuntimeException $e) {
                            $this->_syncLogger->info($product->getEntityId().' - '.$e->getMessage());
                        } catch (Zend_Db_Statement_Exception $e){
                            $this->_syncLogger->info($product->getEntityId().' - '.$e->getMessage());
                        }
                    } while ($retry);
                }
            } else {

                $result = false;
            }

        } else {
            $result = false;
        }

        return $result;
    }

    private function getFileCurl($url, $buyUrl){
        ob_start();

        $newFileName = false;
        $tmpDir = $this->_mediaPath;
        $this->_file->checkAndCreateFolder($tmpDir); 

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, self::HEADERS );
        curl_setopt($curl, CURLOPT_ENCODING, "gzip");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($curl);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);
        $curl = null;
        unset($curl);
        // Si no obtiene nada se devuelve false
        if($data === FALSE) {
            $result = false;
        } else {
            // Se obtiene el tipo de documento devuelto
            if( in_array($contentType, self::CONTENT_TYPE_IMAGE) ){

                if($contentType = 'application/octet-stream'){
                    $ext = pathinfo($url, PATHINFO_EXTENSION);
                    if( in_array($ext, self::CONTENT_TYPE_IMAGE) ) {
                        $contentType = $ext; 
                    } else {
                        $contentType = false; 
                    }
                }

                if($contentType){
                    // Se construye nombre del archivo en formato md5 con el buyurl
                    $this->_syncLogger->info("Valid format: ".$contentType.', Url: '.$url);
                    $newFileName = $tmpDir .'/'. md5($buyUrl) . '.' . $this->_extension[$contentType];
                }

            } else {
                // No tiene formato valido se devuelve false.
                $this->_syncLogger->error("Invalid format: ".$contentType.', Url: '.$url);
                $result = false;
            } 
            if($newFileName){
                // Se guarda el archivo en ruta temporal
                $save = file_put_contents( $newFileName , $data );
                if( !($save === FALSE) ) {
                    $result = $newFileName;
                } else {
                    // Si no es posible guardarlo se devuelve false
                    $this->_syncLogger->error( "Error saving file:" . $newFileName );
                    $result = false;
                }
            } else {
                $result = false; 
            }
        }
        $data = null;
        unset($data);
        ob_end_clean();
        gc_collect_cycles();
        return $result;

    }

    public function is_image($filename) { 
        
        $a = @getimagesize($filename); 
        $image_type = $a[2]; 
        if(in_array($image_type , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP))) { 
            if(filesize($filename) > 1){
                return true;                
            }
        } 
        $this->_syncLogger->info('Second verify format image failed: '.basename($filename));
        return false; 
    }

}