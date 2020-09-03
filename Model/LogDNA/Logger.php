<?php

namespace Rely\Payment\Model\LogDNA;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Monolog\LoggerFactory;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;
use Zwijn\Monolog\Handler\LogdnaHandlerFactory;
use Monolog\Logger as MoloLog;

/**
 * class Logger
 *
 * @description Logger for Rely Module
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 * Logger for Rely Module
 */

class Logger
{
    /**
     * @var LoggerFactory
     */
    private $dnaLoggerFactory;
    /**
     * @var LogdnaHandlerFactory
     */
    private $logDnaHandlerFactory;
    /**
     * @var ApiManagement
     */
    private $apiManagement;
    /**
     * @var Encrypted
     */
    private $encrypted;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Logger constructor.
     * @param LoggerFactory $dnaLoggerFactory
     * @param Encrypted $encrypted
     * @param ScopeConfigInterface $scopeConfig
     * @param LogdnaHandlerFactory $logDnaHandlerFactory
     */
    public function __construct(
        LoggerFactory $dnaLoggerFactory,
        Encrypted $encrypted,
        ScopeConfigInterface $scopeConfig,
        LogdnaHandlerFactory $logDnaHandlerFactory
    ) {
        $this->dnaLoggerFactory = $dnaLoggerFactory;
        $this->logDnaHandlerFactory = $logDnaHandlerFactory;
        $this->encrypted = $encrypted;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $appName
     * @return MoloLog
     */
    public function getLogDNALogger($appName = 'rely_name')
    {
        $appName = 'magento2-' . $this->getDecryptedConf('payment/relypayment/marchant_id');
        $logger =  $this->dnaLoggerFactory->create(['name'=>'general']);
        $logDnaHandler = $this->logDnaHandlerFactory->create(
            ['ingestion_key' => $this->getAPIKey(),
                'hostname'=>$appName,
                'level'=> MoloLog::DEBUG]
        );
        $logger->pushHandler($logDnaHandler);
        return $logger;
    }
    public function getAPIKey()
    {
        return '6f7f4bf22993ea923e39764acc8bfc45';
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getDecryptedConf($path)
    {
        return $this->encrypted->processValue($this->scopeConfig->getValue($path));
    }

    /**
     * @param $message
     * @param $context
     */
    public function debug($message, $context)
    {
        $logger = $this->getLogDNALogger();
        $logger->debug(
            'magento2-'.$message,
            $context
        );
    }
}
