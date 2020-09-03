<?php

namespace Rely\Payment\Block\Adminhtml\Refund\Order;

use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Form
    as MagentoCreditMemoForm;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class Form extends MagentoCreditMemoForm
{
    public function getSaveUrl()
    {
        return $this->getUrl('rely/refund/submit', ['_current' => true]);
    }
}
