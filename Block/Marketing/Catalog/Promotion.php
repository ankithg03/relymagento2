<?php

namespace Rely\Payment\Block\Marketing\Catalog;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Rely\Payment\Block\Marketing\AbstractBlock;
use Rely\Payment\Block\Marketing\Cart\Promotion as CartPromotion;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class Promotion extends AbstractBlock
{
    const BANNER_SLIDER = 'payment/rely_marketing/product_page/banner_strip';

    const PROMOTION = 'payment/rely_marketing/product_page/display_tagline';

    const POPUP = 'payment/rely_marketing/product_page/display_widget';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(Template\Context $context, ScopeConfigInterface $scopeConfig, array $data = [])
    {
        parent::__construct($context, $scopeConfig, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return mixed
     */
    public function isBannerSliderEnabled()
    {
        return $this->scopeConfig->getValue(self::BANNER_SLIDER);
    }

    /**
     * @return mixed
     */
    public function isPromotionTaglineEnabled()
    {
        return $this->scopeConfig->getValue(self::PROMOTION);
    }

    /**
     * @return mixed
     */
    public function isPopUpEnabled()
    {
        return $this->scopeConfig->getValue(self::POPUP);
    }
}
