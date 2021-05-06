<?php
/**
* Comando CLI para la sincronización de productos desde Feed (Cache)
* Desarrollado por: Daniel Rodríguez
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

class SyncImage extends Command
{
	/* 
	* @var \Magento\Framework\App\State State
	*/
	protected $_state;

	/* 
	* @var \DRS\SyncFeed\Service\ImportImageService ImportImageService
	*/
	protected $_importimageservice;

    /**
     * Constructor
     *
     * @param \DRS\SyncFeed\Service\ImportImageService $importimageservice
     *
     * @return void
     */
	public function __construct(
		\Magento\Framework\App\State $state,
		\DRS\SyncFeed\Service\ImportImageService $importimageservice
	){
		ini_set("memory_limit",-1);
		$this->_state = $state;
		$this->_importimageservice = $importimageservice;
		parent::__construct();
	}

    /**
     * Configures arguments and display options for this command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('syncfeed:sync:images');
        $this->setDescription('DRS - SyncFeed Module - Load Missing images of products');
        parent::configure();
    }

    /**
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
        $this->_importimageservice->execute( $output , true );
	}

}