<?php

namespace Rely\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Rely\Payment\Block\Marketing\Cart\Promotion;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;
use Rely\Payment\Model\Config\ModuleConfigurations;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */
class ConfigProvider implements ConfigProviderInterface /**phpcs:ignore*/
{
    const CODE = 'rely_payment';
    /**
     * @var ScopeInterface
     */
    private $scope;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var AssetRepository
     */
    private $assetRepo;
    /**
     * @var ModuleConfigurations
     */
    private $moduleConfigurations;

    /**
     * ConfigProvider constructor.
     * @param ScopeConfigInterface $scope
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfigurations $moduleConfigurations
     * @param AssetRepository $assetRepo
     */
    public function __construct(
        ScopeConfigInterface $scope,
        StoreManagerInterface $storeManager,
        ModuleConfigurations $moduleConfigurations,
        AssetRepository $assetRepo
    ) {
        $this->scope = $scope;
        $this->storeManager = $storeManager;
        $this->assetRepo = $assetRepo;
        $this->moduleConfigurations = $moduleConfigurations;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'enable' => $this->isEnable(),
                    'in_context' => $this->scope->getValue('payment/relypayment/in_context_checkout')?true:false,
                    'logo' => $this->getLogo()
                ]
            ]
        ];
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        return $this->scope->getValue('payment/relypayment/active') &&
            $this->availableCurrency()?true:false;
    }

    public function availableCurrency()
    {
        try {
            $currentCurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
            if ($this->moduleConfigurations->getPaymentEnvironment()==='live') {
                if ($currentCurrencyCode === ApiManagement::SINGAPORE_CURRENCY_CODE
                    || $currentCurrencyCode === ApiManagement::MALAYSIAN_CURRENCY_CODE) {
                    return true;
                }
            } elseif ($this->moduleConfigurations->getPaymentEnvironment()==='sandbox') {
                if ($currentCurrencyCode === ApiManagement::SINGAPORE_CURRENCY_CODE) {
                    return true;
                }
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return false;
    }

    public function getRelyPaymentType()
    {
        return $this->scope->getValue(Promotion::RELY_PAYMENT_TYPE);
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        if ($this->getRelyPaymentType()==='rely_instalment') {
            return  $this->assetRepo->getUrl('Rely_Payment::images/installment/logo.png');
        } else {
            return  $this->assetRepo->getUrl('Rely_Payment::images/paylater/logo.png');
        }
    }
}
