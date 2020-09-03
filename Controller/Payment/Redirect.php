<?php

namespace Rely\Payment\Controller\Payment;

use Magento\Checkout\Model\Session\SuccessValidator;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Rely\Payment\Api\Data\RelyPaymentDataManagementInterface;
use Rely\Payment\Api\RelyPaymentManagementInterface;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;
use Rely\Payment\Logger\Logger;
use Rely\Payment\Model\Config\ModuleConfigurations;
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

class Redirect extends Action
{
    /**
     * @var Onepage
     */
    private $onePage;
    /**
     * @var SuccessValidator
     */
    private $successValidator;
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var ApiManagement
     */
    private $apiManagement;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var RelyPaymentManagementInterface
     */
    private $relyPaymentManagement;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var RelyPaymentDataManagementInterface
     */
    private $relyPaymentDataManagement;
    /**
     * @var ModuleConfigurations
     */
    private $moduleConfigurations;
    /**
     * @var LogDNALogger
     */
    private $logDNALogger;

    /**
     * Redirect constructor.
     * @param Context $context
     * @param Onepage $onePage
     * @param SuccessValidator $successValidator
     * @param ApiManagement $apiManagement
     * @param RelyPaymentManagementInterface $relyPaymentManagement
     * @param ModuleConfigurations $moduleConfigurations
     * @param Logger $logger
     * @param LogDNALogger $logDNALogger
     * @param RelyPaymentDataManagementInterface $relyPaymentDataManagement
     * @param Json $json
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        Onepage $onePage,
        SuccessValidator $successValidator,
        ApiManagement $apiManagement,
        RelyPaymentManagementInterface $relyPaymentManagement,
        ModuleConfigurations $moduleConfigurations,
        Logger $logger,
        LogDNALogger $logDNALogger,
        RelyPaymentDataManagementInterface $relyPaymentDataManagement,
        Json $json,
        Curl $curl
    ) {
        parent::__construct($context);
        $this->onePage = $onePage;
        $this->successValidator = $successValidator;
        $this->curl = $curl;
        $this->apiManagement = $apiManagement;
        $this->json = $json;
        $this->relyPaymentManagement = $relyPaymentManagement;
        $this->logger = $logger;
        $this->relyPaymentDataManagement = $relyPaymentDataManagement;
        $this->moduleConfigurations = $moduleConfigurations;
        $this->logDNALogger = $logDNALogger;
    }

    /**
     * Order success action
     *
     * @return ResultInterface
     *
     */
    public function execute()
    {
        $logDNALogger = $this->logDNALogger;

        $session = $this->onePage->getCheckout();
        if ($this->successValidator->isValid()) {
            $apiResponse = $this->relyPaymentDataManagement->preparePlaceOrder($session->getLastRealOrder());
            if (isset($apiResponse['response'])) {
                $response = $apiResponse['response'];
            } else {
                $resultUrl = $this->_url->getUrl($apiResponse['route']);
            }
            if (isset($response)) {
                if (isset($response['error'])) {
                     $incrementId = $session->getLastRealOrder()->getIncrementId();
                    try {
                        $this->apiManagement->restoreQuote($incrementId);
                    } catch (AlreadyExistsException $e) {
                        $this->logger->info('Quote with order increment id' . $incrementId . 'is already restored');
                    }
                    $errorMessage = $response['error'];
                    $message = isset($errorMessage) ? $this->apiManagement->prepareRelyErrorMessage($errorMessage) : '';
                    $resultUrl = $this->_url->getUrl('checkout') . '#payment';
                    $status = false;
                    $logDNALogger->debug(
                        $session->getLastRealOrder()->getIncrementId(),
                        [
                        'message' => $this->json->serialize($response)
                        ]
                    );
                    $this->logger->info($this->json->serialize($response));
                } elseif (isset($response['errors'])) {
                    $incrementId = $session->getLastRealOrder()->getIncrementId();
                    try {
                        $this->apiManagement->restoreQuote($incrementId);
                    } catch (AlreadyExistsException $e) {
                        $this->logger->info('Quote with order increment id' . $incrementId . 'is already restored');
                    }
                    $this->logger->info($this->json->serialize($response['errors']));
                    $logDNALogger->debug(
                        $incrementId,
                        [
                            'status'=>false,
                            'message'=>$response['error']
                        ]
                    );
                    $message = __('Something went wrong, please contact admin for more details.');
                } elseif (isset($response)) {
                    $isTransactionSaved = $this->relyPaymentDataManagement->saveTransaction($response);
                    if ($isTransactionSaved) {
                        $resultUrl = $response['success_url'];
                        $transactionId = $response['transaction_id'];
                        $orderId = $response['order_id'];
                        $status = true;
                        $logDNALogger->debug(
                            $response['order_id'],
                            [
                            'status' => 'rely_payment_pending',
                            'message' => RelyMessages::RELY_PAYMENT_NOT_DONE
                            ]
                        );

                    } else {
                        $incrementId = $session->getLastRealOrder()->getIncrementId();
                        try {
                            $this->apiManagement->restoreQuote($incrementId);
                        } catch (AlreadyExistsException $e) {
                            $this->logger->info('Quote with order increment id' . $incrementId . 'is already restored');
                        }
                        $resultUrl = $this->_url->getUrl('checkout') . '#payment';
                        $status = false;
                        $logDNALogger->debug(
                            $response['order_id'],
                            [
                            'status' => 'rely_payment_pending',
                            'message' => RelyMessages::RELY_SOMETHING_WENT_WRONG
                            ]
                        );
                        $this->logger->info($this->json->serialize($response));
                    }
                }
            } else {
                $status = false;
                $resultUrl = $this->_url->getUrl('checkout') . '#payment';
                $incrementId = $session->getLastRealOrder()->getIncrementId();

                try {
                    $this->apiManagement->restoreQuote($incrementId);
                } catch (AlreadyExistsException $e) {
                    $this->logger->info('Quote with order increment id' . $incrementId . 'is already restored');
                }
                $message = RelyMessages::RELY_REQUEST_NOT_COMPLETED;
            }
        } else {
            $resultUrl = $this->_url->getUrl('rely/payment/response');
            $placeOrderFirst = RelyMessages::RELY_PLEASE_PLACE_THE_ORDER;
            $this->messageManager->addErrorMessage(__($placeOrderFirst));
        }
            $resultFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        if (isset($resultUrl) && isset($status)) {
            $returnResponse =  [
                'status' => $status,
                'success_url' => $resultUrl
            ];
            $returnResponse['message'] = isset($message)?$message:null;
            $resultFactory->setData(
                $returnResponse
            );
        } else {
            $resultFactory->setData([
                'error' => RelyMessages::RELY_SOMETHING_WENT_WRONG
            ]);
        }
            return $resultFactory;
    }
}
