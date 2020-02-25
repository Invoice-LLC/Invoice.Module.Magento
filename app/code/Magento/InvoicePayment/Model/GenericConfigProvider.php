<?php
namespace Magento\InvoicePayment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\InterkassaPayment\Gateway\Http\Client\ClientMock;

class GenericConfigProvider implements ConfigProviderInterface
{
    const CODE = 'invoice_payment';

    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}