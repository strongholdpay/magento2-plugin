/*browser:true*/
/*global define*/
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
        rendererList.push(
            {
                type: 'strongholdpay_checkout',
                component: 'StrongholdPay_Checkout/js/view/payment/method-renderer/strongholdpay_checkout-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
