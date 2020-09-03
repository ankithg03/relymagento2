<?php

namespace Rely\Payment\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;
use Rely\Payment\Logger\Logger;
use Rely\Payment\Model\Exceptions\ZipCodeException;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class BeforeCheckout implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ApiManagement
     */
    private $apiManagement;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * BeforeCheckout constructor.
     * @param Logger $logger
     * @param ApiManagement $apiManagement
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Logger $logger,
        ApiManagement $apiManagement,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->logger = $logger;
        $this->apiManagement = $apiManagement;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /**
         * @var Order $order
         */
        $order = $observer->getData('order');
        if ($order->getPayment()->getMethod() === 'relypayment') {
             $this->apiManagement->validateOrderForRely($order);
             $order->setCanSendNewEmailFlag(false);
        }
    }
}
