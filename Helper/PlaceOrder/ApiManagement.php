<?php

namespace Rely\Payment\Helper\PlaceOrder;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\SessionFactory as CheckoutSessionFactory;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceFactory;
use Magento\Sales\Model\OrderFactory as OrderModelFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Rely\Payment\Logger\Logger;
use Rely\Payment\Model\Config\ModuleConfigurations;
use Rely\Payment\Model\Exceptions\InvalidInputException;
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

class ApiManagement
{
    const SIMPLE_PRODUCT = 'simple';

    const SANDBOX_RELY_CREATE_ORDER = 'https://demo.rely.sg/api/v1/order/checkout';

    const LIVE_RELY_CREATE_ORDER = 'https://app.rely.sg/api/v1/order/checkout';

    const SANDBOX_RELY_CHECK_ORDER_STATUS = 'https://demo.rely.sg/api/v1/order/status';

    const LIVE_RELY_CHECK_ORDER_STATUS = 'https://app.rely.sg/api/v1/order/status';

    const LIVE_RELY_MALAYSIAN_END_POINT = 'https://app.rely.my/api/v1/order/';

    const LIVE_RELY_SINGAPORE_END_POINT = 'https://app.rely.sg/api/v1/order/';

    const SANDBOX_RELY_SINGAPORE_END_POINT = 'https://demo.rely.sg/api/v1/order/';

    const SINGAPORE_CURRENCY_CODE = 'SGD';

    const MALAYSIAN_CURRENCY_CODE = 'MYR';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var PageFactory
     */
    private $resultPageFactory;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Image
     */
    private $image;
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var OrderModelFactory
     */
    private $order;
    /**
     * @var OrderResourceModel
     */
    private $orderResourceModel;
    /**
     * @var OrderConfig
     */
    private $orderConfig;
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteResourceModel
     */
    private $quoteResourceModel;
    /**
     * @var CartInterface
     */
    private $cart;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var InvoiceFactory
     */
    private $invoiceFactory;
    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;
    /**
     * @var CreditmemoService
     */
    private $creditmemoService;
    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $orderStatusRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Encrypted
     */
    private $encrypted;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var InvoiceService
     */
    private $invoiceService;
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;
    /**
     * @var \Rely\Payment\Model\LogDNA\Logger
     */
    private $logDNA;
    /**
     * @var ModuleConfigurations
     */
    private $moduleConfigurations;
    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * ApiManagement constructor.
     * @param StoreManagerInterface $storeManager
     * @param PageFactory $resultPageFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     * @param UrlInterface $url
     * @param Curl $curl
     * @param Json $json
     * @param OrderModelFactory $order
     * @param OrderConfig $orderConfig
     * @param OrderResourceModel $orderResourceModel
     * @param QuoteFactory $quoteFactory
     * @param QuoteResourceModel $quoteResourceModel
     * @param CartInterface $cart
     * @param CheckoutSessionFactory $checkoutSession
     * @param CreditmemoFactory $creditmemoFactory
     * @param InvoiceFactory $invoiceFactory
     * @param CreditmemoService $creditmemoService
     * @param OrderStatusHistoryRepositoryInterface $orderStatusRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param Encrypted $encrypted
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param \Rely\Payment\Model\LogDNA\Logger $logDNA
     * @param ModuleConfigurations $moduleConfigurations
     * @param Logger $logger
     * @param Image $image
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        UrlInterface $url,
        Curl $curl,
        Json $json,
        OrderModelFactory $order,
        OrderConfig $orderConfig,
        OrderResourceModel $orderResourceModel,
        QuoteFactory $quoteFactory,
        QuoteResourceModel $quoteResourceModel,
        CartInterface $cart,
        CheckoutSessionFactory $checkoutSession,
        CreditmemoFactory $creditmemoFactory,
        InvoiceFactory $invoiceFactory,
        CreditmemoService $creditmemoService,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        Encrypted $encrypted,
        InvoiceSender $invoiceSender,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        \Rely\Payment\Model\LogDNA\Logger $logDNA,
        ModuleConfigurations $moduleConfigurations,
        Logger $logger,
        Image $image
    ) {
        $this->storeManager = $storeManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
        $this->image = $image;
        $this->url = $url;
        $this->json = $json;
        $this->curl = $curl;
        $this->order = $order;
        $this->orderResourceModel = $orderResourceModel;
        $this->orderConfig = $orderConfig;
        $this->quoteFactory = $quoteFactory;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        $this->invoiceFactory = $invoiceFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->encrypted = $encrypted;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->logDNA = $logDNA;
        $this->moduleConfigurations = $moduleConfigurations;
        $this->orderSender = $orderSender;
    }

    /**
     * @param $order Order
     * @return array
     * @throws NoSuchEntityException
     */
    public function getRequestData($order)
    {
        $shippingAddressData = $order->getShippingAddress();
        $shippingAddress = [
            'first_name' => $shippingAddressData->getFirstname(),
            'last_name' => $shippingAddressData->getLastname(),
            'address1' => $shippingAddressData->getStreetLine(1),
            'address2' => $shippingAddressData->getStreetLine(2),
            'state' => $shippingAddressData->getRegion(),
            'zip' => $shippingAddressData->getPostcode(),
            'country' => $shippingAddressData->getCountryId(),
            'email' => $shippingAddressData->getEmail(),
            'phone' => $shippingAddressData->getTelephone(),
        ];
        $billingAddressData = $order->getBillingAddress();

        $billingAddress = [
            'first_name' => $billingAddressData->getFirstname(),
            'last_name' => $billingAddressData->getLastname(),
            'address1' => $billingAddressData->getStreetLine(1),
            'address2' => $billingAddressData->getStreetLine(2),
            'state' => $billingAddressData->getRegion(),
            'zip' => $billingAddressData->getPostcode(),
            'country' => $billingAddressData->getCountryId(),
            'email' => $billingAddressData->getEmail(),
            'phone' => $billingAddressData->getTelephone(),
        ];
        $productDetails = [];
        foreach ($order->getItems() as $item) {
            if ($item->getProductType() === self::SIMPLE_PRODUCT) {
                $productDetails[] = [
                    'sku_number' => $item->getSku(),
                    'name' => $item->getName(),
                    'quantity' => (int)$item->getQtyOrdered(),
                    'amount' => $this->reFormatFloat($item->getPrice()),
                    'description' => $item->getDescription(),
                    'category' => null,
                    'seller_id' => $item->getSellerId(),
                    'seller_name' => $item->getSellerName(),
                    'item_url' => $this->productRepository->getById($item->getProductId())->getProductUrl()
                ];
            }
        }
        $orderDetail = [
            'id' => $order->getIncrementId(),
            'subtotal' => $this->reFormatFloat($order->getSubtotal()),
            'discount_amount' => $this->reFormatFloat($order->getDiscountAmount()),
            'tax' => $this->reFormatFloat($order->getTaxAmount()),
            'shipping_amount' => $this->reFormatFloat($order->getShippingAmount()),
            'total_due' => $this->reFormatFloat($order->getTotalDue()),
            'items' => $productDetails
        ];

        return [
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'order' => $orderDetail,
            'currency_code' => $order->getOrderCurrencyCode(),
            'merchant_key' => $this->moduleConfigurations->getRelyMerchantKey(),//'12294877102101349994938422497818',
            'merchant_id' =>$this->moduleConfigurations->getRelyMerchantId(),// '3634414760550285',
            'decline_url' => $this->url->getUrl('rely/payment/response'),
            'cancel_url' => $this->url->getUrl('rely/payment/response'),
            'notify_url' => $this->url->getUrl('rely/payment/notify'),
            'success_url' => $this->url->getUrl('rely/payment/response'),
            'pending_url' => $this->url->getUrl('rely/payment/response')
        ];
    }

    /**
     * @param $productId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getThumbnailImage($productId)
    {
        $product = $this->productRepository->getById($productId);
        if ($product->getThumbnail()) {
            $productThumbnail = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) .
                'catalog/product' .
                $product->getThumbnail();
        } else {
            $productThumbnail = $this->image->getDefaultPlaceholderUrl('small_image');
        }
        return $productThumbnail;
    }

    /**
     * @param $number
     * @return string
     */
    public function reFormatFloat($number)
    {
        return number_format((float)$number, 2, '.', '');
    }

    /**
     * @param null $currencyCode
     * @return bool|string
     */
    public function getLiveEndPoint($currencyCode = null)
    {
        try {
            if ($currencyCode!=null) {
                if ($currencyCode === self::SINGAPORE_CURRENCY_CODE) {
                    return self::LIVE_RELY_SINGAPORE_END_POINT;
                } elseif ($currencyCode === self::MALAYSIAN_CURRENCY_CODE) {
                    return self::LIVE_RELY_MALAYSIAN_END_POINT;
                } else {
                    return false;
                }
            } else {
                $currentCurrencyCode = $this->moduleConfigurations->getCurrentCurrencyCode();
                if ($currentCurrencyCode === self::SINGAPORE_CURRENCY_CODE) {
                    return self::LIVE_RELY_SINGAPORE_END_POINT;
                } elseif ($currentCurrencyCode === self::MALAYSIAN_CURRENCY_CODE) {
                    return self::LIVE_RELY_MALAYSIAN_END_POINT;
                } else {
                    return false;
                }
            }

        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param null $currencyCode
     * @return bool|string
     */
    public function getSandboxEndPoint($currencyCode = null)
    {
        try {
            if ($currencyCode!=null) {
                if ($currencyCode === self::SINGAPORE_CURRENCY_CODE) {
                    return self::SANDBOX_RELY_SINGAPORE_END_POINT;
                } else {
                    return false;
                }
            } else {
                $currentCurrencyCode = $this->moduleConfigurations->getCurrentCurrencyCode();
                if ($currentCurrencyCode === self::SINGAPORE_CURRENCY_CODE) {
                    return self::SANDBOX_RELY_SINGAPORE_END_POINT;
                } else {
                    return false;
                }
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
    /**
     * @return string
     */
    public function getPlaceOrderUri()
    {
        return $this->moduleConfigurations->getPaymentEnvironment() === 'live' ?
            $this->getLiveEndPoint() . 'checkout' : $this->getSandboxEndPoint() . 'checkout';
    }

    /**
     * @param null $orderCode
     * @return string
     */
    public function getOrderStatusUri($orderCode = null)
    {
        return $this->moduleConfigurations->getPaymentEnvironment() === 'live' ?
            $this->getLiveEndPoint($orderCode) . 'status' : $this->getSandboxEndPoint($orderCode) . 'status';
    }

    /**
     * @param $currencyCode
     * @return string
     */
    public function getOrderCancelUri($currencyCode)
    {
        return $this->moduleConfigurations->getPaymentEnvironment() === 'live' ?
            $this->getLiveEndPoint($currencyCode) . 'cancel' : $this->getSandboxEndPoint($currencyCode) . 'cancel';
    }
    /**
     * @return string
     */
    public function getOrderRefundUri()
    {
        return $this->moduleConfigurations->getPaymentEnvironment() === 'live' ?
            $this->getLiveEndPoint() . 'refund' : $this->getSandboxEndPoint() . 'refund';
    }

    public function getOrderVerifyUri()
    {
        return $this->moduleConfigurations->getPaymentEnvironment() === 'live' ?
            $this->getLiveEndPoint() . 'validate' : $this->getSandboxEndPoint() . 'validate';
    }

    /**
     * @param $requestUrl
     * @param $preparedInputData
     * @return array|bool|float|int|mixed|string|null
     */
    public function postCurl($requestUrl, $preparedInputData)
    {
        $this->curl->setHeaders(["Content-Type" => "application/json"]);
        $this->curl->setOptions([
            CURLOPT_URL => $requestUrl,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $this->curl->post($requestUrl, $preparedInputData);
        return $this->json->unserialize($this->curl->getBody());
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function approveOrder($orderId)
    {
        $orderStatus = $this->moduleConfigurations->getRelyApproveStatus();
        try {
            if ($this->changeOrderStateByConfig($orderId, $orderStatus) === $orderStatus) {
                return true;
            }
        } catch (AlreadyExistsException $e) {
            return false;
        }
        return false;
    }

    /**
     * @param $orderId
     */
    public function generateInvoice($orderId)
    {
        $order = $this->getOrder($orderId);
        if ($order->canInvoice()) {
            try {
                $invoice = $this->invoiceService->prepareInvoice($order);
                if (!$invoice->getTotalQty()) {
                    $invoiceCannotBeGenerated = RelyMessages::RELY_INVOICE_CANT_BE_GENERATED;
                    $this->logger->info(__($invoiceCannotBeGenerated));
                }
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $invoice->getOrder()->setCustomerNoteNotify(true);
                $invoice->getOrder()->setIsInProcess(true);
                $order->addCommentToStatusHistory('Automatically INVOICED', false);
                $transactionSave = $this->transactionFactory
                    ->create()
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
                $this->invoiceSender->send($invoice);

            } catch (LocalizedException $e) {
                $this->logger->info($e->getMessage());
            } catch (Exception $e) {
                $this->logger->info(RelyMessages::RELY_INVOICE_CANT_BE_GENERATED_NOW);
            }
        }
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function generateOrderEmail($orderId)
    {
        $order = $this->getOrder($orderId);
        $this->orderSender->send($order);
        return true;
    }
    /**
     * @param $orderId
     * @return bool
     */
    public function declineOrder($orderId)
    {
        $orderStatus = $this->moduleConfigurations->getRelyDeclineStatus();
        try {
            if ($this->changeOrderStateByConfig($orderId, $orderStatus) === $orderStatus) {
                return true;
            }
        } catch (AlreadyExistsException $e) {
            return false;
        }
        return false;
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function cancelOrder($orderId)
    {
            $orderStatus = $this->moduleConfigurations->getRelyCancelStatus();

        try {
            if ($this->changeOrderStateByConfig($orderId, $orderStatus) === $orderStatus) {
                return true;
            }
        } catch (AlreadyExistsException $e) {
            return true;
        }
        return false;
    }

    /**
     * @param $orderId
     * @param $amount
     * @return bool
     * @throws Exception
     */
    public function refundOrder($orderId, $amount)
    {
        $order = $this->getOrder($orderId);
        try {
            $creditMemo = $this->creditmemoFactory->createByOrder($order);
            $this->creditmemoService->refund($creditMemo);
            return true;
        } catch (AlreadyExistsException $e) {
            return false;
        }
        $order->setTotalRefunded($amount);
        $orderStatus = Order::STATE_CLOSED;
        $orderState = $this->getStateByStatus($orderStatus);
        if ($order->getState() !== $orderState) {
            $order->setState($orderState);
        }
        if ($order->getStatus() !== $orderStatus) {
            $order->setStatus($orderStatus);
        }
        $this->orderResourceModel->save($order);
    }

    /**
     * @param $orderId
     * @param $orderStatus
     * @return string|null
     * @throws AlreadyExistsException
     */
    public function changeOrderStateByConfig($orderId, $orderStatus)
    {
        $order = $this->order->create();
        $this->orderResourceModel->load($order, $orderId, 'increment_id');
        $orderState = $this->getStateByStatus($orderStatus);
        if ($order->getState() !== $orderState) {
            $order->setState($orderState);
        }
        if ($order->getStatus() !== $orderStatus) {
            $order->setStatus($orderStatus);
        }
        $this->orderResourceModel->save($order);
        return $order->getStatus();
    }

    /**
     * @param $orderStatus
     * @return int|string|null
     */
    public function getStateByStatus($orderStatus)
    {
        foreach ($this->orderConfig->getStates() as $state => $stateLabel) {
            foreach ($this->orderConfig->getStateStatuses($state) as $status => $statusLabel) {
                if ($status === $orderStatus) {
                    return $state;
                }
            }
        }
        return null;
    }

    /**
     * @param $orderIncrementId
     * @throws AlreadyExistsException
     */
    public function restoreQuote($orderIncrementId)
    {
        /**
         *  @var $checkoutSession Session
         */
        $checkoutSession = $this->checkoutSession->create();
        $order = $this->getOrder($orderIncrementId);
        $quoteModel = $this->quoteFactory->create();
        $this->quoteResourceModel->load($quoteModel, $order->getQuoteId());
        $quoteModel->setReservedOrderId(null);
        $quoteModel->setIsActive(true);
        $quoteModel->removePayment();
        $this->quoteResourceModel->save($quoteModel);
        $checkoutSession->replaceQuote($quoteModel);
    }

    /**
     * @param $orderIncrementId
     * @return Order
     */
    public function getOrder($orderIncrementId)
    {
        $orderModel = $this->order->create();
        $this->orderResourceModel->load($orderModel, $orderIncrementId, 'increment_id');
        return $orderModel;
    }

    /**
     * @param $orderId
     * @param $comment
     * @return OrderStatusHistoryInterface|null
     */
    public function postComment($orderId, $comment)
    {
        $order = null;
        try {
            $orderId = $this->getOrder($orderId)->getId();
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $exception) {
            $this->logger->info($exception->getMessage());
        }
        $orderHistory = null;
        if ($order) {
            $comment = $order->addCommentToStatusHistory(
                __($comment)
            );
            try {
                $orderHistory = $this->orderStatusRepository->save($comment);
            } catch (Exception $exception) {
                $this->logger->info($exception->getMessage());
            }
        }
        return $orderHistory;
    }

    /**
     * @param $path
     * @return string
     */
    public function getDecryptedConf($path)
    {
        return $this->encrypted->processValue($this->scopeConfig->getValue($path));
    }

    /**
     * @param Order $order
     * @throws LocalizedException
     */
    public function validateOrderForRely($order)
    {
            $prepareData = $this->getRequestData($order);
            $validateUri = $this->getOrderVerifyUri();
        try {
                $validateResponse = $this->postCurl($validateUri, $this->json->serialize($prepareData));
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidInputException(
                __('Something Went wrong in establishing connection with rely')
            );
        } catch (\Exception $exception) {
            throw new InvalidInputException(
                __('please check your internet connection and try again later')
            );
        }
            $this->logger->info($this->json->serialize($validateResponse));

        if (isset($validateResponse['error'])) {
                $errorResponse = $this->prepareRelyErrorMessage($validateResponse['error']);
                $this->logDNA->debug(
                    'Order Validation - Order ID',
                    [
                        'status'=> false,
                        'message' => $errorResponse
                    ]
                );
                throw new InvalidInputException(
                    __($errorResponse)
                );
        } elseif (isset($validateResponse['errors'])) {
            $this->logDNA->debug(
                'Credential',
                [
                    'status'=> false,
                    'message' => $validateResponse['errors']
                ]
            );
            $this->logger->info($this->json->serialize($validateResponse['errors']));
            throw new InvalidInputException(
                __('Something went wrong, please contact admin for more details.')
            );
        }
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function validateCurrencyCodeForRely($currencyCode)
    {
        $validationFlag = true;
        if ($currencyCode ==='SGD' || $currencyCode ==='MYR') {
            return true;
        }
        return $validationFlag;
    }
    /**
     * @param $zipCode
     * @return bool
     */
    public function validateZipCodeForRely($zipCode)
    {
        $validationFlag = false;
        if ($zipCode) {
            if (strlen($zipCode)<=10) {
                $validationFlag = true;
            }
        }

        return $validationFlag;
    }

    /**
     * @param $countryCode
     * @return bool
     */
    public function validateCountryCodeForRely($countryCode)
    {
        $validationFlag = false;
        if ($countryCode) {
            if (strlen($countryCode)<=4) {
                $validationFlag = true;
            }
        }

        return $validationFlag;
    }

    /**
     * @param $phoneNumber
     * @return bool
     */
    public function validatePhoneNumberForRely($phoneNumber)
    {
        $validationFlag = false;
        if ($phoneNumber) {
            if (strlen($phoneNumber)<=15) {
                $validationFlag = true;
            }
        }

        return $validationFlag;
    }

    /**
     * @param $name
     * @return bool
     */
    public function validateNameForRely($name)
    {
        $validationFlag = false;
        if ($name) {
            if (strlen($name)<=120) {
                $validationFlag = true;
            }
        }

        return $validationFlag;
    }

    /**
     * @param $order
     * @throws InvalidInputException
     */
    public function validateCurrencyCode($order)
    {
        $validateCurrencyCode = $this->validateCurrencyCodeForRely($order->getBaseCurrencyCode());
        if (!$validateCurrencyCode) {
            $invalidCurrencyCode = RelyMessages::INVALID_CURRENCY_CODE;
            throw new InvalidInputException(
                __($invalidCurrencyCode)
            );
        }
    }

    /**
     * @param Order\Address $shippingAddress
     * @param OrderAddressInterface $billingAddress
     * @throws InvalidInputException
     */
    public function validateRegion(Order\Address $shippingAddress, OrderAddressInterface $billingAddress)
    {
        $validateShippingRegion = $this->validateNameForRely($shippingAddress->getRegion());
        $validateBillingRegion = $this->validateNameForRely($billingAddress->getRegion());
        $invalidShippingBillingRegion = RelyMessages::INVALID_SHIPPING_BILLING_REGION;
        $invalidShippingRegion = RelyMessages::INVALID_SHIPPING_REGION;
        $invalidBillingRegion = RelyMessages::INVALID_BILLING_REGION;

        $this->validateShippingBillingAddressParam(
            $validateShippingRegion,
            $validateBillingRegion,
            $invalidShippingBillingRegion,
            $invalidShippingRegion,
            $invalidBillingRegion
        );
    }

    /**
     * @param Order\Address $shippingAddress
     * @param OrderAddressInterface $billingAddress
     * @throws InvalidInputException
     */
    public function validateLastName(Order\Address $shippingAddress, OrderAddressInterface $billingAddress)
    {
        $validateShippingLastName = $this->validateNameForRely($shippingAddress->getLastname());
        $validateBillingLastName = $this->validateNameForRely($billingAddress->getLastname());
        $invalidShippingBillingLastName = RelyMessages::INVALID_SHIPPING_BILLING_LAST_NAME;
        $invalidShippingLastName = RelyMessages::INVALID_SHIPPING_LAST_NAME;
        $invalidBillingLastName = RelyMessages::INVALID_BILLING_LAST_NAME;

        $this->validateShippingBillingAddressParam(
            $validateShippingLastName,
            $validateBillingLastName,
            $invalidShippingBillingLastName,
            $invalidShippingLastName,
            $invalidBillingLastName
        );
    }

    /**
     * @param Order\Address $shippingAddress
     * @param OrderAddressInterface $billingAddress
     * @throws InvalidInputException
     */
    public function validateFirstName(Order\Address $shippingAddress, OrderAddressInterface $billingAddress)
    {
        $validateShippingFirstName = $this->validateNameForRely($shippingAddress->getFirstname());
        $validateBillingFirstName = $this->validateNameForRely($billingAddress->getFirstname());
        $invalidShippingBillingFirstName = RelyMessages::INVALID_SHIPPING_BILLING_FIRST_NAME;
        $invalidShippingFirstName = RelyMessages::INVALID_SHIPPING_FIRST_NAME;
        $invalidBillingFirstName = RelyMessages::INVALID_BILLING_FIRST_NAME;
        $this->validateShippingBillingAddressParam(
            $validateShippingFirstName,
            $validateBillingFirstName,
            $invalidShippingBillingFirstName,
            $invalidShippingFirstName,
            $invalidBillingFirstName
        );
    }

    /**
     * @param Order\Address $shippingAddress
     * @param OrderAddressInterface $billingAddress
     * @throws InvalidInputException
     */
    public function validatePhoneNumber(Order\Address $shippingAddress, OrderAddressInterface $billingAddress)
    {
        $validateShippingPhoneNumber = $this->validatePhoneNumberForRely($shippingAddress->getTelephone());
        $validateBillingPhoneNumber = $this->validatePhoneNumberForRely($billingAddress->getTelephone());
        $invalidShippingBillingPhoneNumber = RelyMessages::INVALID_SHIPPING_BILLING_PHONE_NUMBER;
        $invalidShippingPhoneNumber = RelyMessages::INVALID_SHIPPING_PHONE_NUMBER;
        $invalidBillingPhoneNumber = RelyMessages::INVALID_BILLING_PHONE_NUMBER;
        $this->validateShippingBillingAddressParam(
            $validateShippingPhoneNumber,
            $validateBillingPhoneNumber,
            $invalidShippingBillingPhoneNumber,
            $invalidShippingPhoneNumber,
            $invalidBillingPhoneNumber
        );
    }

    /**
     * @param Order\Address $shippingAddress
     * @param OrderAddressInterface $billingAddress
     * @throws InvalidInputException
     * @throws LocalizedException
     */
    public function validateCountryCode(Order\Address $shippingAddress, OrderAddressInterface $billingAddress)
    {
        $validateShippingCountryCode = $this->validateCountryCodeForRely($shippingAddress->getCountryId());
        $validateBillingCountryCode = $this->validateCountryCodeForRely($billingAddress->getCountryId());
        $invalidShippingBillingCountryCode = RelyMessages::INVALID_SHIPPING_BILLING_COUNTRY_CODE;
        $invalidShippingCountryCode = RelyMessages::INVALID_SHIPPING_COUNTRY_CODE;
        $invalidBillingCountryCode = RelyMessages::INVALID_BILLING_COUNTRY_CODE;

        $this->validateShippingBillingAddressParam(
            $validateShippingCountryCode,
            $validateBillingCountryCode,
            $invalidShippingBillingCountryCode,
            $invalidShippingCountryCode,
            $invalidBillingCountryCode
        );
    }

    /**
     * @param Order\Address $shippingAddress
     * @param OrderAddressInterface $billingAddress
     * @throws InvalidInputException
     */
    public function validatePostalCode(Order\Address $shippingAddress, OrderAddressInterface $billingAddress)
    {
        $validateShippingPostalCode = $this->validateZipCodeForRely($shippingAddress->getPostcode());
        $validateBillingPostalCode = $this->validateZipCodeForRely($billingAddress->getPostcode());
        $invalidShippingBillingPostalCode = RelyMessages::INVALID_SHIPPING_BILLING_POSTAL_CODE;
        $invalidShippingPostalCode = RelyMessages::INVALID_SHIPPING_POSTAL_CODE;
        $invalidBillingPostalCode = RelyMessages::INVALID_BILLING_POSTAL_CODE;
        $this->validateShippingBillingAddressParam(
            $validateShippingPostalCode,
            $validateBillingPostalCode,
            $invalidShippingBillingPostalCode,
            $invalidShippingPostalCode,
            $invalidBillingPostalCode
        );
    }

    /**
     * @param $validateShippingParam
     * @param $validateBillingParam
     * @param $invalidShippingBillingParam
     * @param $invalidShippingParam
     * @param $invalidBillingParam
     * @throws InvalidInputException
     */
    public function validateShippingBillingAddressParam(
        $validateShippingParam,
        $validateBillingParam,
        $invalidShippingBillingParam,
        $invalidShippingParam,
        $invalidBillingParam
    ) {
        if (!$validateShippingParam) {
            if ($validateShippingParam === $validateBillingParam) {
                throw new InvalidInputException(
                    __($invalidShippingBillingParam)
                );
            } else {
                throw new InvalidInputException(
                    __($invalidShippingParam)
                );
            }
        }
        if (!$validateBillingParam) {
            throw new InvalidInputException(
                __($invalidBillingParam)
            );
        }
    }

    /**
     * @param array $validateResponse
     * @return string
     */
    public function prepareRelyErrorMessage(array $validateResponse)
    {
        $errorResponse = '';
        $lastKey = array_keys($validateResponse);
        $lastKey = count($lastKey) > 1 ? end($lastKey) : false;
        foreach ($validateResponse as $whatIsError => $errors) {
            if ($whatIsError === $lastKey) {
                $errorResponse = $errorResponse . 'and ' . $whatIsError . ' : ';
                foreach ($errors as $error) {
                    $errorResponse = $errorResponse . rtrim($error, ".") . ', ';
                }
                $errorResponse = rtrim($errorResponse, ", ");
            } elseif (!$lastKey) {
                $errorResponse = $errorResponse . $whatIsError . ' : ';
                foreach ($errors as $error) {
                    $errorResponse = $errorResponse . rtrim($error, ".") . ', ';
                }
                $errorResponse = rtrim($errorResponse, ", ");
            } else {
                $errorResponse = $errorResponse . $whatIsError . ' : ';
                foreach ($errors as $error) {
                    $errorResponse = $errorResponse . rtrim($error, ".") . ', ';
                }
            }
        }
        return $errorResponse;
    }
}
