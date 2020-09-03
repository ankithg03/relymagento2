<?php

namespace Rely\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Serialize\Serializer\Json;
use Rely\Payment\Api\Data\RelyPaymentDataManagementInterface;
use Rely\Payment\Api\RelyPaymentManagementInterface;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;
use Rely\Payment\Logger\Logger;
use Rely\Payment\Model\Config\ModuleConfigurations;
use Rely\Payment\Model\LogDNA\Logger as LogDNALogger;
use Rely\Payment\Model\RelyMessages;
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

class Response extends Action
{
    /**
     * @var Json
     */
    private $json;
    /**
     * @var ApiManagement
     */
    private $apiManagement;
    /**
     * @var RelyPaymentManagementInterface
     */
    private $relyPaymentManagement;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var LogDNALogger
     */
    private $logDNA;
    /**
     * @var RelyPaymentDataManagementInterface
     */
    private $relyPaymentDataManagement;
    /**
     * @var ModuleConfigurations
     */
    private $moduleConfigurations;

    /**
     * Response constructor.
     * @param Context $context
     * @param Json $json
     * @param ApiManagement $apiManagement
     * @param Logger $logger
     * @param LogDNALogger $logDNA
     * @param RelyPaymentDataManagementInterface $relyPaymentDataManagement
     * @param ModuleConfigurations $moduleConfigurations
     * @param RelyPaymentManagementInterface $relyPaymentManagement
     */
    public function __construct(
        Context $context,
        Json $json,
        ApiManagement $apiManagement,
        Logger $logger,
        LogDNALogger $logDNA,
        RelyPaymentDataManagementInterface $relyPaymentDataManagement,
        ModuleConfigurations $moduleConfigurations,
        RelyPaymentManagementInterface $relyPaymentManagement
    ) {
        parent::__construct($context);
        $this->json = $json;
        $this->apiManagement = $apiManagement;
        $this->relyPaymentManagement = $relyPaymentManagement;
        $this->logger = $logger;
        $this->logDNA = $logDNA;
        $this->relyPaymentDataManagement = $relyPaymentDataManagement;
        $this->moduleConfigurations = $moduleConfigurations;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $response = $this->getRequest()->getParams();
        $returnUrl = '';
        $logDNALogger = $this->logDNA;
        $invalidRequest = RelyMessages::RELY_INVALID_REQUEST;
        $paymentNotDone = RelyMessages::RELY_PAYMENT_NOT_DONE;
        $paymentDeclined = RelyMessages::RELY_PAYMENT_DECLINED_MESSAGE;
        $paymentCancelled = RelyMessages::RELY_PAYMENT_CANCELLED;

        if (isset($response['transaction_id']) && isset($response['order_id'])) {
            try {
                $orderStatusResponse = $this->relyPaymentDataManagement->getOrderStatus($response);
            } catch (\Exception $exception) {
                $this->logger->info($exception->getMessage());
                $orderStatusResponse = null;
            }
            $model = $this->relyPaymentManagement->load($response['order_id'], 'order_id');
            $isDataExist = false;
            $somethingWentWrong = RelyMessages::RELY_SOMETHING_WENT_WRONG;
            if (!isset($orderStatusResponse['errors'])) {
                if ($model->getStatus() === null ||
                    ($model->getTransactionId() === $response['transaction_id'])
                ) {
                    $isDataExist = $this->relyPaymentDataManagement->updateOrderTransaction(
                        $model,
                        $orderStatusResponse
                    );
                }

                if (isset($orderStatusResponse['status']) && $orderStatusResponse['status'] === 'charge_succeeded') {
                    $returnUrl = $this->orderApprove($isDataExist, $orderStatusResponse, $logDNALogger);
                } elseif (isset($orderStatusResponse['status']) &&
                    (
                        $orderStatusResponse['status'] === 'charge_failed' ||
                        $orderStatusResponse['status'] ==='cancelled'
                    )
                ) {
                    try {
                        $returnUrl = $this->cancelOrder(
                            $isDataExist,
                            $logDNALogger,
                            $orderStatusResponse,
                            $paymentNotDone,
                            $paymentDeclined,
                            $paymentCancelled
                        );
                    } catch (AlreadyExistsException $e) {
                        $this->logger->info(RelyMessages::RELY_ORDER_ALREADY_CANCELLED);
                    }
                } elseif (isset($orderStatusResponse['status']) &&
                    $orderStatusResponse['status'] === 'authorise_under_review') {
                    $returnUrl = $this->paymentUnderReview(
                        $logDNALogger,
                        $orderStatusResponse,
                        $paymentNotDone,
                        $isDataExist
                    );
                }
                if (!$isDataExist) {
                    $returnUrl = $this->transactionAlreadyExists(
                        $logDNALogger,
                        $response,
                        $invalidRequest,
                        $orderStatusResponse,
                        $somethingWentWrong
                    );
                }
            } elseif (isset($orderStatusResponse)) {
                $orderStatusResponse['order_id'] = $response['order_id'];
                $returnUrl = $this->responseError($logDNALogger, $orderStatusResponse, $somethingWentWrong);
            } else {
                $this->messageManager->addErrorMessage(
                    __($somethingWentWrong)
                );
                $returnUrl = $this->_url->getUrl('checkout/cart');
            }
        } else {
            $this->messageManager->addErrorMessage(
                __($invalidRequest)
            );
            $returnUrl = $this->_url->getUrl('checkout/cart');
        }
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($returnUrl);
        return $resultRedirect;
    }

    /**
     * @param $isDataExist
     * @param array $orderStatusResponse
     * @param LogDNALogger $logDNALogger
     * @return string
     */
    public function orderApprove($isDataExist, array $orderStatusResponse, LogDNALogger $logDNALogger)
    {
        if ($isDataExist) {
            $returnUrl = $this->_url->getUrl('checkout/onepage/success');
            $this->apiManagement->approveOrder($orderStatusResponse['order_id']);
            $this->apiManagement->generateOrderEmail($orderStatusResponse['order_id']);
            $this->apiManagement->generateInvoice($orderStatusResponse['order_id']);
            $logDNALogger->debug(
                $orderStatusResponse['order_id'],
                [
                    'status' => $orderStatusResponse['status'],
                    'message' => RelyMessages::RELY_PAYMENT_DONE
                ]
            );
        } else {
            $returnUrl =  $this->_url->getUrl('checkout/cart');
        }
        return $returnUrl;
    }

    /**
     * @param $isDataExist
     * @param LogDNALogger $logDNALogger
     * @param array $orderStatusResponse
     * @param $paymentNotDone
     * @param $paymentDeclined
     * @param $paymentCancelled
     * @return string
     * @throws AlreadyExistsException
     */
    public function cancelOrder(
        $isDataExist,
        LogDNALogger $logDNALogger,
        array $orderStatusResponse,
        $paymentNotDone,
        $paymentDeclined,
        $paymentCancelled
    ) {
        if ($isDataExist) {
            $logDNALogger->debug(
                $orderStatusResponse['order_id'],
                [
                    'failure' => $orderStatusResponse['status'] === 'charge_failed' ?
                        __($paymentDeclined):__($paymentNotDone),
                    'status' => $orderStatusResponse['status']
                ]
            );
            $orderStatusResponse['status'] === 'charge_failed' ?
                $this->messageManager->addErrorMessage(
                    __($paymentDeclined)
                ) : $this->messageManager->addWarningMessage(
                    __($paymentCancelled)
                );
            $this->apiManagement->declineOrder($orderStatusResponse['order_id']);
            $this->apiManagement->restoreQuote($orderStatusResponse['order_id']);
        }
        return $this->_url->getUrl('checkout/cart');
    }

    /**
     * @param LogDNALogger $logDNALogger
     * @param array $orderStatusResponse
     * @param $paymentNotDone
     * @param $isDataExist
     * @return string
     */
    public function paymentUnderReview(
        LogDNALogger $logDNALogger,
        array $orderStatusResponse,
        $paymentNotDone,
        $isDataExist
    ) {
        $relyPaymentIssue = RelyMessages::RELY_PAYMENT_ISSUE;
        $logDNALogger->debug(
            $orderStatusResponse['order_id'],
            [
                'failure' => __($paymentNotDone),
                'status' => $orderStatusResponse['status']
            ]
        );
        if ($isDataExist) {
            $logDNALogger->debug(
                $orderStatusResponse['order_id'],
                [
                    'failure' => __($paymentNotDone),
                    'status' => $orderStatusResponse['status']
                ]
            );
            $message =
                $relyPaymentIssue;
            $this->messageManager->addWarningMessage(
                __($message)
            );
            $order = $this->apiManagement->getOrder($orderStatusResponse['order_id']);
            $returnUrl = $this->_url->getUrl('sales/order/view', ['order_id' => $order->getIncrementId()]);
        }
        return $returnUrl;
    }

    /**
     * @param LogDNALogger $logDNALogger
     * @param array $response
     * @param $invalidRequest
     * @param array $orderStatusResponse
     * @param $somethingWentWrong
     * @return string
     */
    public function transactionAlreadyExists(
        LogDNALogger $logDNALogger,
        array $response,
        $invalidRequest,
        array $orderStatusResponse,
        $somethingWentWrong
    ) {
        $logDNALogger->debug(
            $response['order_id'],
            [
                'failure' => __($invalidRequest),
                'status' => $orderStatusResponse['status']
            ]
        );
        $this->messageManager->addErrorMessage(
            __($somethingWentWrong)
        );
        $returnUrl = $this->_url->getUrl('checkout/cart');
        return $returnUrl;
    }

    /**
     * @param LogDNALogger $logDNALogger
     * @param array $orderStatusResponse
     * @param $somethingWentWrong
     * @return string
     */
    public function responseError(
        LogDNALogger $logDNALogger,
        array $orderStatusResponse,
        $somethingWentWrong
    ) {
        $logDNALogger->debug(
            $orderStatusResponse['order_id'],
            [
                'errors' => $this->json->serialize($orderStatusResponse['errors']),
                'status' =>
                    isset($orderStatusResponse['status'])?
                        $orderStatusResponse['status']:RelyMessages::RELY_STATUS_NOT_FOUND
            ]
        );
        $this->messageManager->addErrorMessage(
            __($somethingWentWrong)
        );
        $returnUrl = $this->_url->getUrl('checkout/cart');
        return $returnUrl;
    }
}
