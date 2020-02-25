/*browser:true*/
/*global define*/
define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'invoice_payment',
                component: 'Magento_InvoicePayment/js/view/payment/method-renderer/invoice_payment'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });