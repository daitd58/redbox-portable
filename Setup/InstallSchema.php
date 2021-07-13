<?php
/**
 * (c) Redbox Parcel Lockers <thamer@redboxsa.com>
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 *
 * Built by Redbox Technologies, <thamer@redboxsa.com>
 */

namespace Redbox\Portable\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Integration\Model\ConfigBasedIntegrationManager;

/**
 * Redbox Shipping InstallSchema class
 *
 * Class InstallSchema
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var ConfigBasedIntegrationManager
     */

    private $integrationManager;

    /**
     * @param ConfigBasedIntegrationManager $integrationManager
     */

    public function __construct(ConfigBasedIntegrationManager $integrationManager)
    {
        $this->integrationManager = $integrationManager;
    }

    /**
     * Install schema
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->integrationManager->processIntegrationConfig(['RedBoxPortableIntegration']);
        $setup->startSetup();
        $quoteTable = 'quote';
        $orderTable = 'sales_order';
        $redboxTable = 'redbox_portable_checkout_address';

        $table = $setup->getConnection()
            ->newTable($setup->getTable($redboxTable))
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true
                ],
                'Entity ID'
            )
            ->addColumn(
                'shipping_address_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Quote Address ID'
            )
            ->addColumn(
                'url_shipping_label',
                Table::TYPE_TEXT,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Url Shipping Label'
            )
            ->addForeignKey(
                $setup->getFkName(
                    $setup->getTable($redboxTable),
                    'shipping_address_id',
                    'quote_address',
                    'address_id'
                ),
                'shipping_address_id',
                $setup->getTable('quote_address'),
                'address_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Redbox Portable Address Table');
        $setup->getConnection()->createTable($table);

        $setup->getConnection()->addIndex(
            $setup->getTable($redboxTable),
            $setup->getIdxName(
                $redboxTable,
                ['entity_id']
            ),
            ['entity_id']
        );

        $setup->getConnection()->addIndex(
            $setup->getTable($redboxTable),
            $setup->getIdxName(
                $redboxTable,
                ['shipping_address_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['shipping_address_id'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        );

        $data = [];
        $statuses = [
            'redbox_portable_expired'  => __('Redbox Portable Expired'),
            'redbox_portable_failed'  => __('Redbox Portable Failed'),
        ];
        foreach ($statuses as $code => $info) {
            $data[] = ['status' => $code, 'label' => $info];
        }
        $setup->getConnection()
            ->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);

        $states = [
            'complete' => [
                'label' => __('Complete'),
                'statuses' => [
                    'redbox_portable_failed' => ['default' => '0']
                ],
                'visible_on_front' => true,
            ],
            'processing' => [
                'label' => __('Processing'),
                'statuses' => [
                    'redbox_portable_expired' => ['default' => '0']
                ],
                'visible_on_front' => true,
            ]
        ];

        $data = [];
        foreach ($states as $code => $info) {
            if (isset($info['statuses'])) {
                foreach ($info['statuses'] as $status => $statusInfo) {
                    $data[] = [
                        'status' => $status,
                        'state' => $code,
                        'is_default' => 1,
                    ];
                }
            }
        }
        $setup->getConnection()->insertArray(
            $setup->getTable('sales_order_status_state'),
            ['status', 'state', 'is_default'],
            $data
        );

        $setup->endSetup();
    }//end install()
}//end class
