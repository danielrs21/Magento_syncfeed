<?php

namespace DRS\SyncFeed\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;
    private $attributeSetFactory;
    private $attributeSet;
    private $categorySetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory, AttributeSetFactory $attributeSetFactory, CategorySetupFactory $categorySetupFactory )
    {
        $this->eavSetupFactory      = $eavSetupFactory;
        $this->attributeSetFactory  = $attributeSetFactory;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /* CREAR SET DE ATRIBUTOS */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

        $attributeSet = $this->attributeSetFactory->create();

        $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

        $data = [
                    'attribute_set_name'=> 'Affiliate',
                    'entity_type_id'    => $entityTypeId,
                    'sort_order'        => 100,
                ];
        $attributeSet->setData($data);
        $attributeSet->validate();
        $attributeSet->save();
        $attributeSet->initFromSkeleton($attributeSetId);
        $attributeSet->save();

        $data = [
                    'attribute_set_name'=> 'Resold',
                    'entity_type_id'    => $entityTypeId,
                    'sort_order'        => 200,
                ];
        $attributeSet->setData($data);
        $attributeSet->validate();
        $attributeSet->save();
        $attributeSet->initFromSkeleton($attributeSetId);
        $attributeSet->save();

        /* CREAR ATRIBUTOS */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'product_sku',
            [
                'type'                      => 'varchar',
                'input'                     => 'text',
                'label'                     => 'Product SKU',
                'required'                  => false,
                'default'                   => '',
                'unique'                    => false,
                'global'                    => 1,
                'visible'                   => true,
                'searchable'                => true,
                'filterable'                => false,
                'comparable'                => true,
                'visible_on_front'          => true,
                'filterable_in_search'      => false,
                'used_in_product_listing'   => true,
                'used_for_sort_by'          => false,
                'visible_in_advanced_search'=> true,
                'wysiwyg_enabled'           => false,
                'required_in_admin_store'   => false,
                'used_in_grid'              => true,
                'visible_in_grid'           => true,
                'filterable_in_grid'        => true,
                'search_weight'             => '3',
                'user_defined'              => true        
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'buy_url',
            [
                'type'                      => 'text',
                'input'                     => 'textarea',
                'label'                     => 'Buy URL',
                'required'                  => false,
                'default'                   => '',
                'unique'                    => true,
                'global'                    => 0,
                'visible'                   => true,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'filterable_in_search'      => false,
                'used_in_product_listing'   => false,
                'used_for_sort_by'          => false,
                'visible_in_advanced_search'=> false,
                'wysiwyg_enabled'           => false,
                'required_in_admin_store'   => false,
                'used_in_grid'              => true,
                'visible_in_grid'           => true,
                'filterable_in_grid'        => true,
                'search_weight'             => '3',
                'user_defined'              => true        
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'manufacturer_sku',
            [
                'type'                      => 'varchar',
                'input'                     => 'text',
                'label'                     => 'Manufacturer SKU',
                'required'                  => false,
                'default'                   => '',
                'unique'                    => false,
                'global'                    => 0,
                'visible'                   => true,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'filterable_in_search'      => false,
                'used_in_product_listing'   => false,
                'used_for_sort_by'          => false,
                'visible_in_advanced_search'=> false,
                'wysiwyg_enabled'           => false,
                'required_in_admin_store'   => false,
                'used_in_grid'              => true,
                'visible_in_grid'           => true,
                'filterable_in_grid'        => true,
                'search_weight'             => '3',
                'user_defined'              => true        
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'upc',
            [
                'type'                      => 'varchar',
                'input'                     => 'text',
                'label'                     => 'UPC',
                'required'                  => false,
                'default'                   => '',
                'unique'                    => false,
                'global'                    => 0,
                'visible'                   => true,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'filterable_in_search'      => false,
                'used_in_product_listing'   => false,
                'used_for_sort_by'          => false,
                'visible_in_advanced_search'=> false,
                'wysiwyg_enabled'           => false,
                'required_in_admin_store'   => false,
                'used_in_grid'              => true,
                'visible_in_grid'           => true,
                'filterable_in_grid'        => true,
                'search_weight'             => '3',
                'user_defined'              => true        
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'seller_id',
            [
                'type'                      => 'varchar',
                'input'                     => 'text',
                'label'                     => 'Seller ID',
                'required'                  => false,
                'default'                   => '',
                'unique'                    => false,
                'global'                    => 0,
                'visible'                   => true,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'filterable_in_search'      => false,
                'used_in_product_listing'   => false,
                'used_for_sort_by'          => false,
                'visible_in_advanced_search'=> false,
                'wysiwyg_enabled'           => false,
                'required_in_admin_store'   => false,
                'used_in_grid'              => true,
                'visible_in_grid'           => true,
                'filterable_in_grid'        => true,
                'search_weight'             => '3',
                'user_defined'              => true        
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'seller_name',
            [
                'type'                      => 'varchar',
                'input'                     => 'text',
                'label'                     => 'Seller Name',
                'required'                  => false,
                'default'                   => '',
                'unique'                    => false,
                'global'                    => 1,
                'visible'                   => true,
                'searchable'                => true,
                'filterable'                => true,
                'comparable'                => true,
                'visible_on_front'          => true,
                'filterable_in_search'      => true,
                'used_in_product_listing'   => true,
                'used_for_sort_by'          => false,
                'visible_in_advanced_search'=> true,
                'wysiwyg_enabled'           => false,
                'required_in_admin_store'   => false,
                'used_in_grid'              => true,
                'visible_in_grid'           => true,
                'filterable_in_grid'        => true,
                'search_weight'             => '3',
                'user_defined'              => true        
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'image_external_url',
            [
                'type'                      => 'text',
                'input'                     => 'textarea',
                'label'                     => 'Image External URL',
                'required'                  => false,
                'default'                   => '',
                'unique'                    => false,
                'global'                    => 0,
                'visible'                   => false,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'filterable_in_search'      => false,
                'used_in_product_listing'   => false,
                'used_for_sort_by'          => false,
                'visible_in_advanced_search'=> false,
                'wysiwyg_enabled'           => false,
                'required_in_admin_store'   => false,
                'used_in_grid'              => false,
                'visible_in_grid'           => false,
                'filterable_in_grid'        => false,
                'search_weight'             => '3',
                'user_defined'              => true        
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'category_origin',
            [
                'type'                      => 'text',
                'input'                     => 'textarea',
                'label'                     => 'Category Origin',
                'required'                  => false,
                'default'                   => '',
                'unique'                    => false,
                'global'                    => 0,
                'visible'                   => true,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'filterable_in_search'      => false,
                'used_in_product_listing'   => false,
                'used_for_sort_by'          => false,
                'visible_in_advanced_search'=> false,
                'wysiwyg_enabled'           => false,
                'required_in_admin_store'   => false,
                'used_in_grid'              => true,
                'visible_in_grid'           => true,
                'filterable_in_grid'        => true,
                'search_weight'             => '3',
                'user_defined'              => true        
            ]
        );

        /* CREAR GRUPO Y ASIGNAR ATRIBUTOS */
        $groupName          = 'Seller Info';
        $entityTypeId       = $eavSetup->getEntityTypeId('catalog_product');
        $attributeSetIds    = $eavSetup->getAllAttributeSetIds($entityTypeId);

        $attributesGeneral = ['product_sku', 'manufacturer_sku', 'upc'];

        $attributesInGroup = ['buy_url', 'image_external_url', 'seller_id', 'seller_name', 'category_origin'];

        foreach($attributeSetIds as $attributeSetId) {

            /* Creación del grupo: Seller Info */ 
            $eavSetup->addAttributeGroup($entityTypeId, $attributeSetId, $groupName, 2);
            $attributeGroupId = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, $groupName);

            /* Asignar atributos al grupo General */
            foreach($attributesGeneral as $attr){
                $attributeId = $eavSetup->getAttributeId($entityTypeId, $attr);
                $eavSetup->addAttributeToSet($entityTypeId, $attributeSetId, 'General', $attributeId, 4);
            }

            /* Asignar atributos al grupo Seller Info */
            foreach($attributesInGroup as $attr){
                $attributeId = $eavSetup->getAttributeId($entityTypeId, $attr);
                $eavSetup->addAttributeToGroup($entityTypeId, $attributeSetId, $attributeGroupId, $attributeId, null);                
            }
        }

        $setup->endSetup();
    }
}