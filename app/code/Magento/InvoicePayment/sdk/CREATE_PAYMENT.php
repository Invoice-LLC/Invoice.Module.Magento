<?php
namespace Magento\InvoicePayment\sdk;

class CREATE_PAYMENT
{
    /**
     * @var ORDER
     */
    public $order;
    /**
     * @var SETTINGS
     */
    public $settings;
    /**
     * @var array
     */
    public $custom_parameters;
    /**
     * @var array(ITEM)
     */
    public $receipt;
}