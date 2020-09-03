<?php


namespace Rely\Payment\Controller\Adminhtml\Refund;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Rely\Payment\Api\RelyPaymentManagementInterface;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;
use Rely\Payment\Logger\Logger;
use Rely\Payment\Model\LogDNA\Logger as DNALogger;
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

class Save extends Action implements HttpPostActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var RelyPaymentManagementInterface
     */
    private $relyPaymentManagement;
    /**
     * @var ApiManagement
     */
    private $apiManagement;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var DNALogger
     */
    private $dnaLogger;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Order constructor.
     * @param Action\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param RelyPaymentManagementInterface $relyPaymentManagement
     * @param ApiManagement $apiManagement
     * @param DNALogger $dnaLogger
     * @param Logger $logger
     * @param Json $json
     */
    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        RelyPaymentManagementInterface $relyPaymentManagement,
        ApiManagement $apiManagement,
        DNALogger $dnaLogger,
        Logger $logger,
        Json $json
    ) {
        parent::__construct($context);
        $this->json = $json;
        $this->dnaLogger = $dnaLogger;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->relyPaymentManagement = $relyPaymentManagement;
        $this->apiManagement = $apiManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $relyOrder = ($this->getRequest()->getParam('rely_order'));
        $LogDNALogger = $this->dnaLogger->getLogDNALogger();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        if ($order->getPayment()->getMethod() === 'relypayment' &&
            $relyOrder['refund_amount'] <= $order->getTotalPaid() &&
            ($relyOrder['refund_amount'] !== null)
        ) {
            $relyOrderTransaction = $this->relyPaymentManagement->load($order->getIncrementId(), 'order_id');
            $orderData = [
                'transaction_id' => $relyOrderTransaction->getTransactionId(),
                'order_id' => $relyOrderTransaction->getOrderId(),
                'merchant_key' =>  $this->apiManagement->getDecryptedConf('payment/relypayment/marchant_key'),
                'merchant_id' =>  $this->apiManagement->getDecryptedConf('payment/relypayment/marchant_id'),
                'reason'=> $relyOrder['refund_reason'],
                'amount'=> $relyOrder['refund_amount'],
            ];
            $orderCurrencyCode = $this->apiManagement
                                      ->getOrder($relyOrderTransaction->getOrderId())
                                      ->getOrderCurrencyCode();
            $orderStatusUri = $this->apiManagement->getOrderStatusUri($orderCurrencyCode);
            try {
                $orderStatus = $this->apiManagement->postCurl($orderStatusUri, $this->json->serialize($orderData));
            } catch (\Exception $exception) {
                $this->logger->info($exception->getMessage());
            }
            $somethingWentWrong = RelyMessages::RELY_SOMETHING_WENT_WRONG;
            $orderNotRefundedMessage = RelyMessages::RELY_ORDER_NOT_REFUNDED;

            if (isset($orderStatus['status'])
                && $orderStatus['status'] === 'charge_succeeded'
                && $order->getState() ===  Order::STATE_PROCESSING
            ) {
                $orderRefundUri = $this->apiManagement->getOrderRefundUri();
                $orderRefundResponse =
                    $this->apiManagement->postCurl(
                        $orderRefundUri,
                        $this->json->serialize($orderData)
                    );
                if (isset($orderRefundResponse['order_id']) &&
                    $orderRefundResponse['order_id'] === $orderData['order_id']
                ) {
                    $orderRefundedMessage = RelyMessages::RELY_ORDER_REFUNDED;
                    if ($this->apiManagement->refundOrder(
                        $orderRefundResponse['order_id'],
                        $relyOrder['refund_amount']
                    )
                    ) {
                        $LogDNALogger->debug(
                            $orderRefundResponse['order_id'],
                            [
                                'success' => $orderStatus['status'],
                                'message'=>__($orderRefundedMessage)
                            ]
                        );

                        $this->apiManagement->postComment(
                            $relyOrderTransaction->getOrderId(),
                            'Reason for Refund : ' . $relyOrder['refund_reason']
                        );
                        $this->messageManager->addSuccessMessage(__($orderRefundedMessage));
                    } else {
                        $LogDNALogger->debug(
                            $orderRefundResponse['order_id'],
                            [
                                'error' => __($orderNotRefundedMessage)
                            ]
                        );
                        $this->messageManager->addErrorMessage(
                            __($orderNotRefundedMessage)
                        );
                    }
                } else {
                    $this->messageManager->addErrorMessage(__($somethingWentWrong));
                }
            } else {
                try {
                    $LogDNALogger->debug(
                        $order->getIncrementId(),
                        [
                            'error' => RelyMessages::RELY_REFUND_TO_PROCESSING
                        ]
                    );
                } catch (\Exception $exception) {
                    $this->logger->info($exception->getMessage());
                }

                    $this->messageManager->addErrorMessage(
                        __($orderNotRefundedMessage)
                    );
            }
        }
        $resultRedirect->setUrl($this->_url->getUrl('sales/order/view', ['order_id' => $orderId]));
        return $resultRedirect;
    }
}
