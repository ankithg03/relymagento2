<?php

namespace Rely\Payment\Model\Config;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class ModuleConfigurations
{
    /**
     * Marketing Type
     */
    const CART_BANNER_STRIP = 'payment/rely_marketing/cart_page/banner_strip';

    const CART_WIDGET = 'payment/rely_marketing/cart_page/display_tagline';

    const CART_POPUP = 'payment/rely_marketing/cart_page/display_widget';

    const CATALOG_BANNER_STRIP = 'payment/rely_marketing/product_page/banner_strip';

    const CATALOG_WIDGET = 'payment/rely_marketing/product_page/display_tagline';

    const CATALOG_POPUP = 'payment/rely_marketing/product_page/display_widget';

    const CATEGORY_BANNER_STRIP = 'payment/rely_marketing/category_page/display_banner_strip';

    const HOME_BANNER_STRIP = 'payment/rely_marketing/home_page/banner_strip';

    const RELY_PRODUCT_TYPE = 'payment/rely_marketing/product_type/marketing_type';

    /**
     * Payment Gateway
     */

    const RELY_IN_CONTEXT_CHECKOUT = 'payment/relypayment/in_context_checkout';

    const CURRENT_CURRENCY_CODE = 'currency/options/base';

    const RELY_PAYMENT_ENVIRONMENT_TYPE = 'payment/relypayment/environment';

    const RELY_CANCEL_STATUS = 'payment/relypayment/canceled_order_status';

    const RELY_DECLINE_STATUS = 'payment/relypayment/declined_order_status';

    const RELY_APPROVE_STATUS = 'payment/relypayment/approved_order_status';

    const RELY_MERCHANT_KEY = 'payment/relypayment/marchant_key';

    const RELY_MERCHANT_ID = 'payment/relypayment/marchant_id';
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Encrypted
     */
    private $encrypted;

    /**
     * ModuleConfigurations constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Encrypted $encrypted
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Encrypted $encrypted
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encrypted = $encrypted;
    }

    /**
     * @return int|null
     */
    public function isCartBannerStripEnabled()
    {
        return $this->scopeConfig->getValue(self::CART_BANNER_STRIP);
    }

    /**
     * @return int|null
     */
    public function isCartWidgetEnabled()
    {
        return $this->scopeConfig->getValue(self::CART_WIDGET);
    }

    /**
     * @return int|null
     */
    public function isCartPopupEnabled()
    {
        return $this->scopeConfig->getValue(self::CART_POPUP);
    }

    /**
     * @return int|null
     */
    public function isCatalogBannerStripEnabled()
    {
        return $this->scopeConfig->getValue(self::CATALOG_BANNER_STRIP);
    }

    /**
     * @return int|null
     */
    public function isCatalogWidgetEnabled()
    {
        return $this->scopeConfig->getValue(self::CATALOG_WIDGET);
    }

    /**
     * @return int|null
     */
    public function isCatalogPopupEnabled()
    {
        return $this->scopeConfig->getValue(self::CATALOG_POPUP);
    }

    /**
     * @return int|null
     */
    public function isCategoryBannerStripEnabled()
    {
        return $this->scopeConfig->getValue(self::CATEGORY_BANNER_STRIP);
    }

    /**
     * @return int|null
     */
    public function isHomeBannerStripEnabled()
    {
        return $this->scopeConfig->getValue(self::HOME_BANNER_STRIP);
    }

    /**
     * @return int|null
     */
    public function getRelyProductType()
    {
        return $this->scopeConfig->getValue(self::RELY_PRODUCT_TYPE);
    }

    /**
     * @return int|null
     */
    public function getRelyInContextCheckout()
    {
        return $this->scopeConfig->getValue(self::RELY_IN_CONTEXT_CHECKOUT);
    }

    /**
     * @return int|null
     */
    public function getCurrentCurrencyCode()
    {
        try {
            return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        } catch (NoSuchEntityException $e) {
            return true;
        }
    }
    /**
     * @return int|null
     */
    public function getPaymentEnvironment()
    {
        return $this->scopeConfig->getValue(self::RELY_PAYMENT_ENVIRONMENT_TYPE);
    }

    /**
     * @return int|null
     */
    public function getRelyCancelStatus()
    {
        return $this->scopeConfig->getValue(self::RELY_CANCEL_STATUS);
    }

    /**
     * @return int|null
     */
    public function getRelyDeclineStatus()
    {
        return $this->scopeConfig->getValue(self::RELY_DECLINE_STATUS);
    }

    /**
     * @return int|null
     */
    public function getRelyApproveStatus()
    {
        return $this->scopeConfig->getValue(self::RELY_APPROVE_STATUS);
    }

    /**
     * @return int|null
     */
    public function getRelyMerchantKey()
    {
        return $this->getDecryptedConf(self::RELY_MERCHANT_KEY);
    }

    /**
     * @return int|null
     */
    public function getRelyMerchantId()
    {
        return $this->getDecryptedConf(self::RELY_MERCHANT_ID);
    }

    /**
     * @param $path
     * @return string
     */
    public function getDecryptedConf($path)
    {
        return $this->encrypted->processValue($this->scopeConfig->getValue($path));
    }
}
