<?php

namespace Rely\Payment\Observer;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Rely\Payment\Api\Data\RelyPaymentDataManagementInterface;
use Rely\Payment\Api\RelyPaymentManagementInterface;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;
use Rely\Payment\Logger\Logger;
use Rely\Payment\Model\LogDNA\Logger as LogDNALogger;
use Rely\Payment\Model\RelyMessages;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class CheckOrderStatus implements ObserverInterface
{

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var UrlInterface
     */
    private $urlInterface;
    /**
     * @var ApiManagement
     */
    private $apiManagement;
    /**
     * @var RelyPaymentManagementInterface
     */
    private $relyPaymentManagement;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var LogDNALogger
     */
    private $dnalogger;
    /**
     * @var RelyPaymentDataManagementInterface
     */
    private $relyPaymentDataManagement;

    /**
     * CheckOrderStatus constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param RelyPaymentManagementInterface $relyPaymentManagement
     * @param ApiManagement $apiManagement
     * @param LogDNALogger $dnalogger
     * @param RelyPaymentDataManagementInterface $relyPaymentDataManagement
     * @param Logger $logger
     * @param Json $json
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        RelyPaymentManagementInterface $relyPaymentManagement,
        ApiManagement $apiManagement,
        LogDNALogger $dnalogger,
        RelyPaymentDataManagementInterface $relyPaymentDataManagement,
        Logger $logger,
        Json $json
    ) {
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->apiManagement = $apiManagement;
        $this->relyPaymentManagement = $relyPaymentManagement;
        $this->json = $json;
        $this->logger = $logger;
        $this->dnalogger = $dnalogger;
        $this->relyPaymentDataManagement = $relyPaymentDataManagement;
    }

    /**
     * @param Observer $observer
     * @return CheckOrderStatus
     */
    public function execute(Observer $observer)
    {
        $LogDNALogger = $this->dnalogger->getLogDNALogger();
        try {
            $order = $observer->getEvent()->getOrder();
            $previousStatus = $order->getOrigData()['status'];
            $customerId = $order->getCustomerId();
            $status = $order->getStatus();
            $orderIncrementId = $order->getIncrementId();
            $payment = $order->getPayment()->getMethod();
            if ($previousStatus != $status &&
                $status === 'canceled' &&
                $payment === 'relypayment'
            ) {
                try {
                    $relyOrderTransaction = $this->relyPaymentManagement->load($orderIncrementId, 'order_id');
                    $orderStatus = $this->relyPaymentDataManagement->getOrderStatus($relyOrderTransaction->getData());
                    if (isset($orderStatus['status']) && $orderStatus['status'] === 'authorise_under_review') {
                        $orderCancelResponse = $this->relyPaymentDataManagement
                            ->cancelOrder($relyOrderTransaction->getData());
                        if (isset($orderCancelResponse['order_id'])) {
                            $order->setStatus('rely_payment_canceled');
                            $orderStatus = $this->relyPaymentDataManagement
                                ->getOrderStatus($relyOrderTransaction->getData());

                            $LogDNALogger->debug(
                                $orderCancelResponse['order_id'],
                                [
                                    'success' => RelyMessages::RELY_ORDER_CANCELLED,
                                    'status' => $orderStatus['status']
                                ]
                            );
                            $relyOrderTransaction->setStatus($orderStatus['status']);

                            $this->relyPaymentManagement->save($relyOrderTransaction);
                            $this->orderRepository->save($order);
                            $this->apiManagement->postComment(
                                $orderIncrementId,
                                'Order at Rely Server has been cancelled'
                            );
                        }
                    } else {
                        $this->logger->info(RelyMessages::RELY_ORDER_NOT_PLACED);
                    }
                } catch (\Exception $exception) {
                    $this->logger->info($exception->getMessage());
                    $LogDNALogger->debug(
                        $orderIncrementId,
                        [
                            'error' => $exception->getMessage(),
                            'message'=>RelyMessages::RELY_CANNOT_ORDER_CANCELLED
                        ]
                    );
                }
            }
            return $this;
        } catch (Exception $exception) {
            $this->logger->info($exception->getMessage());
            $LogDNALogger->debug(
                'order_cancel',
                [
                    'error' => $exception->getMessage(),
                ]
            );
            return $this;
        }
    }
}
