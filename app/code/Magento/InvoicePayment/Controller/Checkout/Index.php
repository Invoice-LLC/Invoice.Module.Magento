<?php
namespace Magento\InvoicePayment\Controller\Checkout;

require_once "../../sdk/RestClient.php";
require_once "../../sdk/CREATE_TERMINAL.php";
require_once "../../sdk/CREATE_PAYMENT.php";
require_once "../../sdk/common/ITEM.php";
require_once "../../sdk/common/ORDER.php";
require_once "../../sdk/common/SETTINGS.php";

use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\Controller\ResultFactory;
use Magento\InvoicePayment\sdk\RestClient;
use Magento\InvoicePayment\sdk\CREATE_PAYMENT;
use Magento\InvoicePayment\sdk\CREATE_TERMINAL;
use Magento\InvoicePayment\sdk\common\ITEM;
use Magento\InvoicePayment\sdk\common\SETTINGS;
use Magento\InvoicePayment\sdk\common\ORDER;

class Index extends \Magento\Framework\App\Action\Action
{
    //TODO: SET FALSE
    const TEST = false;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    private $configWriter;

    /**
     * @var \Magento\Store\Model\WebsiteRepository
     */
    private $websiteRepository;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var RestClient
     */
    private $client;

    private $terminal_id;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Store\Model\WebsiteRepository $websiteRepository,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    )
    {
        $this->jsonResultFactory = $jsonResultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
        $this->websiteRepository = $websiteRepository;
        $this->curl = $curl;
        $this->checkoutSession = $checkoutSession;
        $this->cacheTypeList = $cacheTypeList;
        $this->resultFactory = $resultFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        header("Content-Type: application/json");

        $order = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Sales\Model\Order')->loadByIncrementId($this->getLastOrderId());

        $api_key = $this->getConfig("api_key");
        $login = $this->getConfig("login");
        $this->terminal_id = $this->getConfig("terminal");

        if($login == null or $api_key == null) {
            return $this->getRedirect("/404/");
        }

        $name = $this->getConfig("name","general/store_information/");
        if($name == null) {
            $name = "Store";
        }

        $this->client = new RestClient($login, $api_key);

        if($this->terminal_id == null) {
            $this->terminal_id = $this->createTerminal($name);
            if($this->terminal_id == null) {
                return $this->getRedirect("/404/");
            }
        }

        $link = $this->createPayment($order);
        if($link == null) {
            $this->terminal_id = $this->createTerminal($name);
            $link = $this->createPayment($order);
            if($link == null) {
                return $this->getRedirect("/404/");
            } else {
                return $this->getRedirect($link);
            }
        } else {
            return $this->getRedirect($link);
        }
    }

    private function createPayment($order) {
        $create_payment = new CREATE_PAYMENT();

        $order_invoice = new ORDER();
        $order_invoice->amount = $order->getGrandTotal();
        $order_invoice->currency = "RUB";
        $order_invoice->id = $order->getIncrementId();
        $create_payment->order = $order_invoice;

        $settings = new SETTINGS();
        $settings->terminal_id = $this->terminal_id;
        $create_payment->settings = $settings;

        $receipt = array();
        foreach ($order->getItems() as $item) {
            $item_invoice = new ITEM();
            $item_invoice->price = $item->getPrice();
            $item_invoice->name = $item->getName();
            $item_invoice->quantity = $item->getQtyOrdered();
            $item_invoice->resultPrice = $item->getQtyOrdered() * $item->getPrice();
            array_push($receipt, $item_invoice);
        }
        $create_payment->receipt = $receipt;
        $payment = $this->client->CreatePayment($create_payment);
        $this->log("PAYMENT ".json_encode($payment) . "\n");
        return @$payment->payment_url;
    }

    private function createTerminal($name) {
        $create_terminal = new CREATE_TERMINAL();
        $create_terminal->name = $name;
        $create_terminal->type = "dynamical";

        $terminal = $this->client->CreateTerminal($create_terminal);
        if($terminal == null or @$terminal->error != null) {
            $this->log("ERROR ".json_encode($terminal) . "\n");
            return null;
        }
        $this->log("TERMINAL ".json_encode($terminal) . "\n");
        $this->setConfig("terminal", $terminal->id);
        return $terminal->id;
    }

    private function getLastOrderId() {
        return $this->checkoutSession->getLastRealOrder()->getIncrementId();
    }

    private function getResult($data) {
        $result = $this->jsonResultFactory->create();
        $result->setData($data);
        return $result;
    }

    private function getRedirect($link) {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($link);

        return $resultRedirect;
    }

    private function setConfig($key, $value, $path = "payment/invoice_payment/") {
        $this->configWriter->save($path.$key, $value);
    }

    private function getConfig($key, $path = "payment/invoice_payment/") {
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
        return $this->scopeConfig->getValue("$path$key", "websites");
    }

    private function log($log) {
        $fp = fopen('invoice_payment.log', 'a+');
        fwrite($fp, $log);
        fclose($fp);
    }
}