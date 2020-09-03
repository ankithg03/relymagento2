define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        if (window.navigator.connection.downlink &&
        window.checkoutConfig.payment.rely_payment.enable ) {
            rendererList.push(
                {
                    type: 'relypayment',
                    component: 'Rely_Payment/js/view/payment/method-renderer/rely-payment'
                }
            );
        }
        return Component.extend({});
    }
);
