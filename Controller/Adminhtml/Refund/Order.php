<?php

namespace Rely\Payment\Controller\Adminhtml\Refund;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Rely\Payment\Helper\PlaceOrder\ApiManagement;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */
class Order extends Action implements HttpGetActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var CreditmemoLoader
     */
    private $creditmemoLoader;
    /**
     * @var PageFactory
     */
    private $resultPageFactory;
    /**
     * @var ForwardFactory
     */
    private $resultForwardFactory;
    /**
     * @var ApiManagement
     */
    private $apiManagement;

    /**
     * Order constructor.
     * @param Action\Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoLoader $creditmemoLoader
     * @param PageFactory $resultPageFactory
     * @param ApiManagement $apiManagement
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        CreditmemoLoader $creditmemoLoader,
        PageFactory $resultPageFactory,
        ApiManagement $apiManagement,
        ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->creditmemoLoader = $creditmemoLoader;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->apiManagement = $apiManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $order = $this->orderRepository->get($this->getRequest()->getParam('order_id'));
        if (!$order->canCreditmemo()) {
            $this->messageManager->addErrorMessage('Cannot Refund Please Generate Invoice');
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $result->setUrl(
                $this->_url->getUrl(
                    'sales/order/view',
                    ['order_id' => $this->getRequest()->getParam('order_id')]
                )
            );
            return $result;
        }
        $this->creditmemoLoader->setOrderId($this->getRequest()->getParam('order_id'));
        $this->creditmemoLoader->setCreditmemoId($this->getRequest()->getParam('creditmemo_id'));
        $this->creditmemoLoader->setCreditmemo($this->getRequest()->getParam('creditmemo'));
        $this->creditmemoLoader->setInvoiceId($this->getRequest()->getParam('invoice_id'));
        $creditmemo = $this->creditmemoLoader->load();
        if ($creditmemo) {
            if ($comment = $this->_objectManager->get(Session::class)->getCommentText(true)) {
                $creditmemo->setCommentText($comment);
            }
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Magento_Sales::sales_order');
            $resultPage->getConfig()->getTitle()->prepend(__('Credit Memos'));
            if ($creditmemo->getInvoice()) {
                $resultPage->getConfig()->getTitle()->prepend(
                    __("New Memo for #%1", $creditmemo->getInvoice()->getIncrementId())
                );
            } else {
                $resultPage->getConfig()->getTitle()->prepend(__("Rely Refund Order"));
            }
            return $resultPage;
        } else {
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('noroute');
            return $resultForward;
        }
    }
}
