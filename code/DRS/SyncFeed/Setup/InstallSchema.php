<?php

namespace DRS\SyncFeed\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
//use Magento\Framework\DB\Ddl\TriggerFactory;
/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    const TABLA_CACHE           = 'drs_syncfeed_cache';
    const TABLA_CATEGORYMATCH   = 'drs_syncfeed_categorymatch';

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
       // TriggerFactory $triggerFactory
    ) {
        $installer = $setup;
        $installer->startSetup();

        /*
         * Create table 'drs_syncfeed_categorymatch'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::TABLA_CATEGORYMATCH)
        )->addColumn(
            'match_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
            'Match Record Id'
        )->addColumn(
            'category_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            32,
            ['nullable' => false],
            'Code for prevent duplicate'
        )->addColumn(
            'category_feed',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65536,
            ['nullable' => false],
            'Feed Category'
        )->addColumn(
            'category_match',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            ['nullable' => true],
            'Match Category'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['default' => 1],
            'Active Status'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false,'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Creation Time'
        )->addColumn(
            'update_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            [],
            'Modification Time'
        )->addIndex(
            $installer->getIdxName(
                self::TABLA_CATEGORYMATCH,
                ['category_code'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            'category_code',
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )->setComment(
            'Category Match Table'
        );
        $installer->getConnection()->createTable($table);

        /* TRIGGER FOR GENERATE MD5 WITH CATEGORY_FEED THATS PREVENTS DUPLICATE ENTRIES */
/*        $trigger = $triggerFactory->create()
            ->setName('category_code_generator')
            ->setTime(\Magento\Framework\DB\Ddl\Trigger::TIME_BEFORE)
            ->setEvent('INSERT')
            ->setTable($setup->getTable(self::TABLA_CATEGORYMATCH));

        $trigger->addStatement('SET NEW.category_code = md5(NEW.category_feed)');

        $installer->getConnection()->createTrigger($trigger);*/

        /*
         * Create table 'drs_syncfeed_cache'
        */
        $table = $installer->getConnection()->newTable(
            $installer->getTable(self::TABLA_CACHE)
        )->addColumn(
            'item_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true, 'auto_increment' => true],
            'Item Id'
        )
        ->addColumn(
            'item_type_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Type of item (1: Affiliate, 2: Coupon; 3: Resold)'
        )
        ->addColumn(
            'item_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Product Name - Magento'
        )
        ->addColumn(
            'item_url_slug',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Product Url-key Magento'
        )
        ->addColumn(
            'item_description',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65536,
            ['nullable' => true],
            'Product Description Magento'
        )
        ->addColumn(
            'item_condition',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Product Condition'
        )
        ->addColumn(
            'item_buy_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65536,
            ['nullable' => false],
            'Product Buy Url'
        )
        ->addColumn(
            'item_image_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65536,
            ['nullable' => false],
            'Product Image Url'
        )
        ->addColumn(
            'item_last_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false,'scale' => '4','precision' => '12'],
            'Product Special Price'
        )
        ->addColumn(
            'item_last_normal_price',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => false,'scale' => '4','precision' => '12'],
            'Product Normal Price'
        )
        ->addColumn(
            'shipping_cost',
            \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            '12,4',
            ['nullable' => true,'scale' => '4','precision' => '12'],
            'Product Shipping Cost'
        )
        ->addColumn(
            'item_sku',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Product SKU'
        )
        ->addColumn(
            'item_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Unique Product Code'
        )
        ->addColumn(
            'item_upc',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Product UPC'
        )
        ->addColumn(
            'manufacturer_sku',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Manufacturer SKU'
        )
        ->addColumn(
            'manufacturer_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Manufacturer Name'
        )
        ->addColumn(
            'item_model',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Product Model'
        )
        ->addColumn(
            'category',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            65536,
            ['nullable' => false],
            'Product Category'
        )
        ->addColumn(
            'store_public_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Store Id'
        )
        ->addColumn(
            'store_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Store Name'
        )
        ->addColumn(
            'item_created',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false,'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Date of created item'
        )
        ->addColumn(
            'item_updated',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => true],
            'Date of updated item'
        )
        ->addColumn(
            'sync_new',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['default' => '1','nullable' => false,'precision' => '3'],
            'Switch for New Product for register in Magento'
        )
        ->addColumn(
            'magento_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Magento Product Id created'
        )
        ->addColumn(
            'item_status',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['default' => '1', 'nullable' => false, 'precision' => '3'],
            'null'
        )
        ->addIndex(
            $installer->getIdxName(
                self::TABLA_CACHE,
                ['item_url_slug'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            'item_url_slug',
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )
        ->addIndex(
            $installer->getIdxName(
                self::TABLA_CACHE,
                ['item_code'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            'item_code',
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )
        ->addIndex(
            $installer->getIdxName(
                self::TABLA_CACHE,
                ['sync_new'],
                null
            ),
            'sync_new',
            []
        )
        ->addIndex(
            $installer->getIdxName(
                self::TABLA_CACHE,
                ['item_status'],
                null
            ),
            'item_status',
            []
        )
        ->setComment(
            'Cache Items Table'
        );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
