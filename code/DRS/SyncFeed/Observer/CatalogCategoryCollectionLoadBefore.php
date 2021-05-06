<?php
/**
 * Este observer inicialmente fue pensado para ocultar categorias vacias
 * Pero debido a que genera mucho retardo en carga del sitio se saco de servicio
 * Igual no funciona bien con el menu amazon. 
 */

namespace DRS\SyncFeed\Observer;

class CatalogCategoryCollectionLoadBefore implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection */
        $categoryCollection = $observer->getCategoryCollection();
        $categoryCollection->addAttributeToSelect('is_anchor');
    }
}