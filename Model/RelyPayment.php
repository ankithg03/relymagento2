<?php

namespace Rely\Payment\Model;

use Magento\Framework\Model\AbstractModel;
use Rely\Payment\Model\ResourceModel\RelyPayment as ResourceModel;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class RelyPayment extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
