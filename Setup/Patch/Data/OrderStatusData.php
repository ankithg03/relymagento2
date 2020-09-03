<?php

namespace Rely\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class OrderStatusData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * AddRelyOrderStates constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /**
         * Prepare database for install
         */
        $this->moduleDataSetup->getConnection()->startSetup();
        $statusData = [];
        $statusStateData = [];
        $statuses = [
            'rely_payment_pending' => ['label' => __('Rely Payment Pending'), 'state' => 'new'],
            'rely_payment_approved' => ['label' => __('Rely Payment Approved'), 'state' => 'processing'],
            'rely_payment_canceled' => ['label' => __('Rely Payment Canceled'), 'state' => 'canceled'],
            'rely_payment_declined' => ['label' => __('Rely Payment Declined'), 'state' => 'canceled']

        ];
        foreach ($statuses as $code => $info) {
            $statusData[] = ['status' => $code, 'label' => $info['label']];
            $statusStateData[] = [
                'status' => $code,
                'state' => $info['state'],
                false,
                $code ==='rely_payment_pending'?false:true
            ];
        }
        $this->moduleDataSetup->getConnection()->insertArray(
            $this->moduleDataSetup->getTable('sales_order_status'),
            ['status', 'label'],
            $statusData
        );
        $this->moduleDataSetup->getConnection()->insertArray(
            $this->moduleDataSetup->getTable('sales_order_status_state'),
            ['status', 'state', 'is_default', 'visible_on_front'],
            $statusStateData
        );
        /**
         * Prepare database after install
         */
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
