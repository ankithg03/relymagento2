<?php

namespace Rely\Payment\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\Order\Config as OrderState;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class OrderStatus implements OptionSourceInterface
{
    /**
     * @var CollectionFactory $statusCollectionFactory
     */
    protected $orderStatusCollectionFactory;
    /**
     * @var OrderState
     */
    private $orderState;

    /**
     * @param OrderState $orderState
     */
    public function __construct(
        OrderState $orderState
    ) {
        $this->orderState = $orderState;
    }

    /**
     * Get order status options
     *
     * @return array
     */
    public function getOrderStatusOptions()
    {
        $stateStatus = [];
        foreach ($this->orderState->getStates() as $state => $stateLabel) {
            foreach ($this->orderState->getStateStatuses($state) as $status => $statusLabel) {
                $stateStatus[] = [
                    'value'=>$status,
                    'label'=>$statusLabel . "[ {$stateLabel} ]"
                ];
            }
        }
        return $stateStatus;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getOrderStatusOptions();
    }
}
