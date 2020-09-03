<?php

namespace Rely\Payment\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Rely\Payment\Logger\Logger;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class Settings
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ArrayUtils
     */
    private $arrayUtils;
    /**
     * @var ModuleConfigurations
     */
    private $moduleConfigurations;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Settings constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfigurations $moduleConfigurations
     * @param Logger $logger
     * @param ArrayUtils $arrayUtils
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ModuleConfigurations $moduleConfigurations,
        Logger $logger,
        ArrayUtils $arrayUtils
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->arrayUtils = $arrayUtils;
        $this->moduleConfigurations = $moduleConfigurations;
        $this->logger = $logger;
    }

    /**
     * @param string $paymentMethodCode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getEnabledForStores($paymentMethodCode)
    {
        $paymentConfigPath = "payment/{$paymentMethodCode}/active";
        $currencyCodes =
            $this->moduleConfigurations->getPaymentEnvironment() === 'live'? "MYR,SGD":"SGD";
        $currentCurrencyPath = 'currency/options/base';
        $values = $this->getValue($paymentConfigPath);
        $values = $values && in_array(
            $this->getValue($currentCurrencyPath),
            explode(',', $currencyCodes)
        );
        if ($values) {
            try {
                return explode(',', $this->storeManager->getStore()->getCode());
            } catch (NoSuchEntityException $e) {
                $this->logger->info($e->getMessage());
            }
        }

        return [];
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * @param $xmlPath
     * @return bool
     * @throws NoSuchEntityException
     */
    protected function getValue($xmlPath)
    {
        return $this->scopeConfig
                ->getValue(
                    $xmlPath,
                    ScopeInterface::SCOPE_STORE,
                    $this->storeManager->getStore()->getCode()
                );
    }
}
