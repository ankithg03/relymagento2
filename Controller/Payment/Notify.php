<?php

namespace Rely\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Rely\Payment\Api\Data\RelyPaymentDataManagementInterface;
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

class Notify extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
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
     * @var LogDNALogger
     */
    private $logDNALogger;
    /**
     * @var RelyPaymentDataManagementInterface
     */
    private $dataManagement;

    /**
     * Notify constructor.
     * @param Context $context
     * @param Logger $logger
     * @param ApiManagement $apiManagement
     * @param RelyPaymentDataManagementInterface $dataManagement
     * @param LogDNALogger $logDNALogger
     */
    public function __construct(
        Context $context,
        Logger $logger,
        ApiManagement $apiManagement,
        RelyPaymentDataManagementInterface $dataManagement,
        LogDNALogger $logDNALogger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->apiManagement = $apiManagement;
        $this->logDNALogger = $logDNALogger;
        $this->dataManagement = $dataManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $response = true;
        if (!$this->dataManagement->prepareNotifyData($this->getRequest())) {
            $response = false;
            $logDNA = $this->logDNALogger;
            try {
                $logDNA
                    ->debug(
                        "web-hooks",
                        [
                            'notified' => 'yes',
                            'message'=> RelyMessages::RELY_SOMETHING_WENT_WRONG
                        ]
                    );
            } catch (\Exception $exception) {
                $this->logger->info($exception->getMessage());
            }
        }
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(['notified'=>$response]);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
