<?php


namespace Rely\Payment\Plugin\Block\Adminhtml\Order;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Block\Adminhtml\Order\View as MagentoOrderView;
use Magento\Sales\Model\Order;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class View
{
    const RELY_PAYMENT_METHOD = 'relypayment';
    const RELY_PAYMENT_PENDING_ORDER_STATUS = 'payment/relypayment/order_status';
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * View constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $url
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
    }

    /**
     * @param MagentoOrderView $subject
     * @return null
     */
    public function beforeGetOrderId(MagentoOrderView $subject)
    {
        $refundUrl = $this->url->getUrl('rely/refund/order', ['order_id'=>$subject->getOrder()->getId()]);
        if ($subject->getOrder()->getState() === Order::STATE_PROCESSING &&
            $subject->getOrder()->getPayment()->getMethod() === self::RELY_PAYMENT_METHOD
        ) {
            $subject->removeButton('order_creditmemo');

            $subject->addButton(
                'refund_rely_order',
                [
                    'label' => __('Refund Rely Order'),
                    'onclick' => 'setLocation(\'' . $refundUrl . '\')',
                    'class' => 'reset'
                ],
                -1
            );
        }

        return null;
    }
}
