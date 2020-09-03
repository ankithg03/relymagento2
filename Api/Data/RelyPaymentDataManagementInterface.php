<?php

namespace Rely\Payment\Api\Data;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Rely\Payment\Model\RelyPayment;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

interface RelyPaymentDataManagementInterface
{
    /**
     * @param Order $order
     * @return array|null
     */
    public function preparePlaceOrder($order);

    /**
     * @param $response
     * @return bool
     */
    public function saveTransaction($response);

    /**
     * @param $response
     * @return array|null
     */
    public function getOrderStatus($response);

    /**
     * @param RelyPayment $model
     * @param $orderStatusResponse
     * @return bool
     */
    public function updateOrderTransaction(RelyPayment $model, $orderStatusResponse);

    /**
     * @param $orderStatusResponse
     * @return bool
     */
    public function cancelOrder($orderStatusResponse);
    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function prepareNotifyData($request);
}
