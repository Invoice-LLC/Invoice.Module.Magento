define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'mage/url'
    ],
    function ($, Component, urlBuilder, url) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Magento_InvoicePayment/payment/invoice_payment'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'invoice_payment';
            },

            isActive: function() {
                return true;
            },

            afterPlaceOrder: function () {
                window.location.href = "/invoice/checkout/index";
            }
        });
    }
);

