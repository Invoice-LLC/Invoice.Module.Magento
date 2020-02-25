<?php
namespace Magento\InvoicePayment\Model\Payment;

class InvoicePayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = "invoice_payment";

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_stripeApi = false;
    protected $_countryFactory;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = array('RUB');

    /**
     * @var $client \RestClient;
     */
    private $client;
    private $terminal;
    private $login;
    private $api_key;

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }

        return $this;
    }

    private function createTerminal() {
        $create_terminal = new \CREATE_TERMINAL();
        $create_terminal->name = "Интернет-магазин";
        $create_terminal->description = "";
        $create_terminal->type = "dynamical";
        $create_terminal->defaultPrice = 1;

        $terminal = $this->client->CreateTerminal($create_terminal);
        $this->terminal = @$terminal->id;
        $this->setConfigData('terminal', $this->terminal);

        return $terminal;
    }

    private function createPayment() {

    }
}