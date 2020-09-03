<?php

namespace Rely\Payment\Model;

/**
 *
 * @description Magento Module for Rely Payment
 * @author   Codilar Team Player <ankith@codilar.com>
 * @license  Open Source
 * @link     https://www.codilar.com
 * @copyright Copyright © 2020 Codilar Technologies Pvt. Ltd.. All rights reserved
 *
 */

class RelyMessages
{
    const RELY_ORDER_CANCELLED = 'The Order has Been Cancelled';

    const RELY_CANNOT_ORDER_CANCELLED = 'The Order Couldn\'t be Cancelled on Rely Server due to some issue';

    const RELY_SOMETHING_WENT_WRONG = 'Something Went Wrong. Please contact admin for more details!';

    const RELY_ORDER_NOT_PLACED = 'The Order is not placed in Rely Server';

    const RELY_STATUS_NOT_FOUND = 'sorry status not found for this particular order';

    const RELY_ORDER_APPROVED_WEB_HOOK = 'Order Approved from Web hook';

    const RELY_ORDER_REFUNDED = 'The Payment has Been Refunded';

    const RELY_ORDER_NOT_REFUNDED = 'The Payment couldn\'t be Refunded, please try again later!';

    const RELY_REFUND_TO_PROCESSING = 'The Payment Can only to refunded for Processing State';

    const RELY_PLEASE_PLACE_THE_ORDER = 'Please Place your Order';

    const RELY_REQUEST_NOT_COMPLETED  = 'Sorry your Request couldn\'t be completed, please try again later';

    const RELY_PAYMENT_NOT_DONE = 'The Payment has not yet been done';

    const RELY_PAYMENT_DECLINED_MESSAGE = 'Sorry Your Payment has been declined Please try again later!';

    const RELY_PAYMENT_CANCELLED = 'Your Payment got Canceled Please try again later!';

    const RELY_PAYMENT_ISSUE = 'The Payment has been done Canceled or Payment has some issue please Contact the admin!';

    const RELY_INVALID_REQUEST = 'Invalid Request';

    const RELY_QUOTE_ALREADY_RESTORED = 'The Quote Already Restored';

    const RELY_ORDER_ALREADY_CANCELLED = 'Order has Already has been cancelled';

    const RELY_INVOICE_CANT_BE_GENERATED = 'You can\'t create an invoice without products.';

    const RELY_INVOICE_CANT_BE_GENERATED_NOW = 'Invoice Couldn\'t be generated at this moment';

    const RELY_PAYMENT_DONE =
        'The Order has been Approved at Magento Side and Invoice has been generated for the payment';

    const RELY_AUTHORIZATION_APPROVED = 'Authorization was approved';

    /**
     * Exceptions
     */
    const INVALID_CURRENCY_CODE = 'Sorry your Currency Code is not valid Please Correct it and place your order';

    const INVALID_SHIPPING_BILLING_REGION =
        'Sorry your Shipping & Billing Address Region is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_REGION =
        'Sorry your Shipping Address Region is not valid Please Correct it and try again.';

    const INVALID_BILLING_REGION =
        'Sorry your Billing Address Region is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_BILLING_LAST_NAME =
        'Sorry your Shipping & Billing Address Last Name is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_LAST_NAME =
        'Sorry your Shipping Address Last Name is not valid Please Correct it and try again.';

    const INVALID_BILLING_LAST_NAME =
        'Sorry your Billing Address Last Name is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_BILLING_FIRST_NAME =
        'Sorry your Shipping & Billing Address First Name is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_FIRST_NAME =
        'Sorry your Shipping Address First Name is not valid Please Correct it and try again.';

    const INVALID_BILLING_FIRST_NAME =
        'Sorry your Billing Address First Name is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_BILLING_PHONE_NUMBER =
        'Sorry your Shipping & Billing Address Phone Number is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_PHONE_NUMBER =
        'Sorry your Shipping Address Phone Number is not valid Please Correct it and try again.';

    const INVALID_BILLING_PHONE_NUMBER =
        'Sorry your Billing Address Phone Number is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_BILLING_COUNTRY_CODE =
        'Sorry your Shipping & Billing Address Country Code is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_COUNTRY_CODE =
        'Sorry your Shipping Address Country Code is not valid Please Correct it and try again.';

    const INVALID_BILLING_COUNTRY_CODE =
        'Sorry your Billing Address Country Code is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_BILLING_POSTAL_CODE =
        'Sorry your Shipping & Billing Address Zip Code is not valid Please Correct it and try again.';

    const INVALID_SHIPPING_POSTAL_CODE =
        'Sorry your Shipping Address Zip Code is not valid Please Correct it and try again.';

    const INVALID_BILLING_POSTAL_CODE =
        'Sorry your Billing Address Zip Code is not valid Please Correct it and try again.';
}
