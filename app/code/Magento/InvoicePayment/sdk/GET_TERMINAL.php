<?php
namespace Magento\InvoicePayment\sdk;

class GET_TERMINAL
{
    /**
     * @var string
     */
    public $alias;
    /**
     * @var string
     */
    public $id;

    public function __construct($alias)
    {
        $this->alias = $alias;
    }
}