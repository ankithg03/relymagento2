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

class ProductType implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label'=>'Rely Installment',
                'value'=>'rely_instalment'
            ],
            [
                'label'=>'Rely Pay Later',
                'value'=>'rely_pay_later'
            ]
        ];
    }
}
