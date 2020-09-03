define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'mage/url',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function (
        $,
        Component,
        quote,
        fullScreenLoader,
        setPaymentInformationAction,
        additionalValidators,
        placeOrder,
        url,
        messageList,
        redirectOnSuccessAction
    ) {
        'use strict';
        return Component.extend({

            defaults: {
                template: 'Rely_Payment/payment/generic'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() &&
                additionalValidators.validate() &&
                this.isPlaceOrderActionAllowed() === true
                ) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                    .done(
                        function (result) {
                            self.afterPlaceOrder();
                            if (self.redirectAfterPlaceOrder) {
                                    $('body').trigger('processStart');
                                    $.ajax({url: url.build('rely/payment/redirect'),
                                        success: function (result) {
                                            $('body').trigger('processStop');
                                            if (result.success_url && result.status) {
                                                if (!window.checkoutConfig.payment.rely_payment.in_context) {
                                                        window.location.href = result.success_url;
                                                } else {
                                                    window.checkoutConfig.payment.rely_payment.success_url = result.success_url;
                                                    let RelyCheckout = function (success_url) {
                                                        rely.checkout(success_url);
                                                    };
                                                    RelyCheckout(result.success_url);
                                                    document.querySelector('[src="http://s3-ap-southeast-1.amazonaws.com/rely-js-sdk/st/icon-close.png"]')? document.querySelector('[src="http://s3-ap-southeast-1.amazonaws.com/rely-js-sdk/st/icon-close.png"]').addEventListener('click', ()=>{
                                                        $('.rely-overlay-container').addClass('active');
                                                    }):null
                                                }
                                            } else if (result.success_url) {
                                                window.location.href = result.success_url;
                                                messageList.addErrorMessage({ message: result.message });
                                            } else if (result.failure_url) {
                                                window.location.href = result.failure_url;
                                            } else {
                                                messageList.addErrorMessage({ message: result.error });

                                            }
                                        }
                                    });
                            } else {

                            }
                        }
                    ).always(
                        function () {
                                self.isPlaceOrderActionAllowed(true);
                        }
                    );

                    return true;
                }

                return false;
            },
            getRelyLogo: function () {
                return window.checkoutConfig.payment.rely_payment.logo;
            },
            focusLiveMode: function () {
                let success_url = window.checkoutConfig.payment.rely_payment.success_url;
                let RelyCheckout = function (success_url) {
                    rely.checkout(success_url);
                };
                $('.rely-overlay-container').removeClass('active');
                RelyCheckout(success_url);
                document.querySelector('[src="http://s3-ap-southeast-1.amazonaws.com/rely-js-sdk/st/icon-close.png"]')? document.querySelector('[src="http://s3-ap-southeast-1.amazonaws.com/rely-js-sdk/st/icon-close.png"]').addEventListener('click', ()=>{
                    $('.rely-overlay-container').addClass('active');
                }):null
            },
            closeLiveMode: function () {
                $('body').trigger('processStart');
                if (!window.checkoutConfig.payment.rely_payment.focusLiveMode.closed) {
                    window.checkoutConfig.payment.rely_payment.focusLiveMode.close();
                    $('.rely-overlay-container').removeClass('active');
                    window.location.href = url.build('checkout/cart');
                }
            }
        });
    }
);
