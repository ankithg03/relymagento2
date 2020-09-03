<?php

namespace Rely\Payment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class StoreViews implements OptionSourceInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    public function toOptionArray()
    {
        $list = [];
        $stores = $this->getAllStores();
        foreach ($stores as $store) {
            $list[] = [
                'value' => $store->getCode(),
                'label' => $store->getName()
            ];
        }

        return $list;
    }

    /**
     * @return StoreInterface[]
     */
    protected function getAllStores()
    {
        return $this->storeManager->getStores();
    }
}
