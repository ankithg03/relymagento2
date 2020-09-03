<?php

namespace Rely\Payment\Model\ResourceModel\RelyPayment;

use Rely\Payment\Model\RelyPayment as Model;
use Rely\Payment\Model\ResourceModel\RelyPayment as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class Collection extends AbstractCollection
{
    protected $_idFieldName = "id";
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
