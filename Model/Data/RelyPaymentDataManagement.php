<?php

namespace Rely\Payment\Model\Data;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Rely\Payment\Api\Data\RelyPaymentDataManagementInterface;
use Rely\Payment\Api\RelyPaymentManagementInterface;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;
use Rely\Payment\Logger\Logger;
use Rely\Payment\Model\Config\ModuleConfigurations;
use Rely\Payment\Model\RelyMessages;
use Rely\Payment\Model\RelyPayment;
use Rely\Payment\Model\LogDNA\Logger as LogDNALogger;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class RelyPaymentDataManagement implements RelyPaymentDataManagementInterface
{
    /**
     * @var ApiManagement
     */
    private $apiManagement;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var RelyPaymentManagementInterface
     */
    private $relyPaymentManagement;
    /**
     * @var ModuleConfigurations
     */
    private $moduleConfigurations;
    /**
     * @var LogDNALogger
     */
    private $logDNALogger;

    /**
     * RelyPaymentDataManagement constructor.
     * @param ApiManagement $apiManagement
     * @param RelyPaymentManagementInterface $relyPaymentManagement
     * @param ModuleConfigurations $moduleConfigurations
     * @param Logger $logger
     * @param LogDNALogger $logDNALogger
     * @param Json $json
     */
    public function __construct(
        ApiManagement $apiManagement,
        RelyPaymentManagementInterface $relyPaymentManagement,
        ModuleConfigurations $moduleConfigurations,
        Logger $logger,
        LogDNALogger $logDNALogger,
        Json $json
    ) {
        $this->apiManagement = $apiManagement;
        $this->logger = $logger;
        $this->json = $json;
        $this->relyPaymentManagement = $relyPaymentManagement;
        $this->moduleConfigurations = $moduleConfigurations;
        $this->logDNALogger = $logDNALogger;
    }

    /**
     * @param Order $order
     * @return array|null
     */
    public function preparePlaceOrder($order)
    {
        $requestUrl = $this->apiManagement->getPlaceOrderUri();
        try {
            $preparedInputData = $this->json->serialize(
                $this->apiManagement->getRequestData($order)
            );
            return [
                        'response' =>$this->apiManagement->postCurl($requestUrl, $preparedInputData)
                ];
        } catch (NoSuchEntityException $e) {
            $this->logger->info(RelyMessages::RELY_ORDER_NOT_PLACED);
        } catch (\Exception $exception) {
            $this->logger->info($exception->getMessage());
            try {
                $this->apiManagement->restoreQuote($order->getIncrementId());
            } catch (AlreadyExistsException $e) {
                $quoteAlreadyRestored = RelyMessages::RELY_QUOTE_ALREADY_RESTORED;
                $this->logger->info(__($quoteAlreadyRestored));
            }
        }
        $orderCouldNotProcessed = RelyMessages::RELY_REQUEST_NOT_COMPLETED;
        return [
            'route'=>    'checkout/cart',
            'message'=> $orderCouldNotProcessed
        ];
    }

    /**
     * @param $response
     * @return bool
     */
    public function saveTransaction($response)
    {
        try {
            $relyOrderTransactionModel = $this->relyPaymentManagement->create();
            $relyOrderTransactionModel->setOrderId($response['order_id']);
            $relyOrderTransactionModel->setTransactionId($response['transaction_id']);
            $relyOrderTransactionModel->setStatus('rely_payment_pending');
            $this->relyPaymentManagement->save($relyOrderTransactionModel);
        } catch (AlreadyExistsException $exception) {
            $this->logger->info('The Transaction Already Exists');
        }
        try {
            $this->apiManagement->postComment(
                $response['order_id'],
                'transaction id : ' .
                $response['transaction_id']
            );
            return true;
        } catch (\Exception $exception) {
            $this->logger->info($exception->getMessage());
        }
        return false;
    }

    /**
     * @param array $response
     * @return array|bool|float|int|mixed|string|null
     */
    public function getOrderStatus($response)
    {
            $orderVerify = [
                'transaction_id' => $response['transaction_id'],
                'order_id' => $response['order_id'],
                'merchant_key' => $this->moduleConfigurations->getRelyMerchantKey(),
                'merchant_id' => $this->moduleConfigurations->getRelyMerchantId()
            ];
            $orderCode = $this->apiManagement->getOrder($response['order_id'])->getOrderCurrencyCode();
            return $this->apiManagement->postCurl(
                $this->apiManagement->getOrderStatusUri($orderCode),
                $this->json->serialize($orderVerify)
            );
    }

    /**
     * @param RelyPayment $model
     * @param $orderStatusResponse
     * @return bool
     */
    public function updateOrderTransaction(RelyPayment $model, $orderStatusResponse)
    {
        $model->setOrderId($orderStatusResponse['order_id']);
        $model->setTransactionId($orderStatusResponse['transaction_id']);
        $model->setStatus($orderStatusResponse['status']);
        $this->relyPaymentManagement->save($model);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function cancelOrder($response)
    {
        $orderData = [
            'transaction_id' => $response['transaction_id'],
            'order_id' => $response['order_id'],
            'merchant_key' => $this->moduleConfigurations->getRelyMerchantKey(),
            'merchant_id' => $this->moduleConfigurations->getRelyMerchantId()
        ];
        $order = $this->apiManagement->getOrder($response['order_id']);
        $currencyCode = $order->getOrderCurrencyCode();
        $orderCancelUrl = $this->apiManagement
            ->getOrderCancelUri($currencyCode);

        return $this->apiManagement
            ->postCurl($orderCancelUrl, $this->json->serialize($orderData));
    }

    /**
     * @inheritDoc
     */
    public function prepareNotifyData($request)
    {
        $logDNALogger = $this->logDNALogger;
        $email = $request->getParam('customer_email');
        $gross = $request->getParam('customer_email');
        $name = $request->getParam('name');
        $orderId = $request->getParam('order_id');
        $paymentStatus = $request->getParam('payment_status');
        $note = $request->getParam('note');
        $transactionId = $request->getParam('transaction_id');
        $merchantId = $request->getParam('merchant_id');
        $this->logger->info($orderId);
        $this->logger->info($transactionId);
        if (isset($transactionId) && isset($orderId)) {
            $relyModel = $this->relyPaymentManagement->load($orderId, 'order_id');
            $statusRequest = ['order_id'=>$orderId, 'transaction_id'=>$transactionId];
            $statusResponse = $this->getOrderStatus($statusRequest);
            if ($relyModel->getTransactionId() === $statusResponse['transaction_id']) {
                $relyModel->setStatus($statusResponse['status']);
                $this->relyPaymentManagement->save($relyModel);
                if ($statusResponse['status']==='authorise_approved') {
                    $this->apiManagement
                        ->postComment($statusResponse['order_id'], RelyMessages::RELY_AUTHORIZATION_APPROVED);
                    return true;
                } elseif ($statusResponse['status']==='charge_succeeded') {
                    return $this->paymentCompletion($statusResponse, $logDNALogger);
                } elseif ($statusResponse['status']==='charge_failed' ||
                    $statusResponse['status'] ==='cancelled'
                ) {
                    return $this->paymentDeclined($statusResponse, $logDNALogger);
                }
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * @param array $statusResponse
     * @param LogDNALogger $logDNALogger
     * @return bool
     */
    public function paymentCompletion($statusResponse, LogDNALogger $logDNALogger)
    {
        if ($this->apiManagement->approveOrder($statusResponse['order_id'])) {
            $this->apiManagement->generateInvoice($statusResponse['order_id']);
            $logDNALogger->debug(
                $statusResponse['order_id'],
                [
                    'status' => $statusResponse['status'],
                    'message' => RelyMessages::RELY_ORDER_APPROVED_WEB_HOOK
                ]
            );
            return true;
        }
        $logDNALogger->debug(
            $statusResponse['order_id'],
            [
                'status' => $statusResponse['status'],
                'message' => RelyMessages::RELY_SOMETHING_WENT_WRONG
            ]
        );
        return false;
    }

    /**
     * @param array $statusResponse
     * @param LogDNALogger $logDNALogger
     * @return bool
     */
    public function paymentDeclined($statusResponse, $logDNALogger)
    {
        try {
            if ($this->apiManagement->declineOrder($statusResponse['order_id'])) {
                $logDNALogger->debug(
                    $statusResponse['order_id'],
                    [
                        'status' => $statusResponse['status'],
                        'message' => RelyMessages::RELY_PAYMENT_DECLINED_MESSAGE
                    ]
                );
                return true;
            } else {
                $logDNALogger->debug(
                    $statusResponse['order_id'],
                    [
                        'status' => $statusResponse['status'],
                        'message' => RelyMessages::RELY_SOMETHING_WENT_WRONG
                    ]
                );
                return false;
            }
        } catch (\Exception $exception) {
            $logDNALogger->debug(
                $statusResponse['order_id'],
                [
                    'status' => $statusResponse['status'],
                    'message' => RelyMessages::RELY_SOMETHING_WENT_WRONG
                ]
            );
            $this->logger->info($exception->getMessage());
            return false;
        }
    }
}
