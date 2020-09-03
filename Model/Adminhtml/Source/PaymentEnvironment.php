<?php

namespace Rely\Payment\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class PaymentEnvironment implements OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
           [
               'value' => 'live',
               'label' => __('Live')
           ],
           [
               'value' => 'sandbox',
               'label' => __('Sandbox Mode')
           ]
        ];
    }
}
