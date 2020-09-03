<?php

namespace Rely\Payment\Block\Marketing;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class AbstractBlock extends Template
{
    const RELY_PAYMENT_TYPE = 'payment/rely_marketing/product_type/marketing_type';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Promotion constructor.
     * @param Template\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return mixed
     */
    public function getRelyPaymentType()
    {
        return $this->scopeConfig->getValue(self::RELY_PAYMENT_TYPE);
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        if ($this->getRelyPaymentType()==='rely_instalment') {
            return  $this->getViewFileUrl('Rely_Payment::images/installment/logo.png');
        } else {
            return  $this->getViewFileUrl('Rely_Payment::images/paylater/logo.png');
        }
    }
}
