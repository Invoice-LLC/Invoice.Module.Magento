<?php
namespace Magento\InvoicePayment\Controller\Callback;

use \Magento\Sales\Model\Order;
use \Magento\Framework\App\ObjectManager;
use \Magento\Framework\App\Request\Http;
use \Magento\Sales\Model\OrderFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Action\Context
     */
    private $context;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var Http
     */
    protected $request;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Http $request,
        OrderFactory $orderFactory,
        \Magento\Sales\Model\Order $order

    ) {
        $this->context = $context;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->order = $order;
        $this->orderFactory = $orderFactory;
        $this->request = $request;

        return parent::__construct($context);
    }

    public function execute() {
        header("Content-Type: application/json");
        $rawBody = $this->getRequest()->getContent();
        $notification = json_decode($rawBody);

        if($rawBody == null or $notification == null or !isset($notification->id) or $notification->id == null) {
            return $this->getResult(["error" => "notification not found"]);
        }

        $oder_id    = @$notification->order->id;

        $signature  = @$notification->signature;
        $tranId     = @$notification->id;
        $status     = @$notification->status;

        if(
            $oder_id    == null or
            $signature  == null or
            $tranId     == null or
            $status     == null
        ) {
            return $this->getResult(["error" => "bad request"]);
        }

        if($signature != $this->getSignature($this->getConfig("api_key"), $status, $tranId)) {
            return $this->getResult(["error" => "wrong signature"]);
        }

        $type = $notification->notification_type;

        switch ($type) {
            case "pay":
                switch ($status) {
                    case "successful":
                        return $this->getResult($this->pay($oder_id));
                        break;
                    case "error":
                        return $this->getResult($this->error($oder_id));
                        break;
                    default:
                        return $this->getResult($this->getResult(["error" => "bad request"]));
                        break;
                }
                break;
            case "refund":
                return $this->getResult($this->refund($oder_id));
                break;
            default:
                return $this->getResult($this->getResult(["error" => "Bad request", "type" => $type]));
                break;
        }
    }

    /**
     * @param $id
     * @return array
     */
    private function pay($id) {
        $order = $this->order->load($id);
        if($order == null) {
            return ["error" => "order not found"];
        }

        $order->setStatus(Order::STATE_COMPLETE);
        $order->setState(Order::STATE_COMPLETE);

        $order->save();

        return ["status" => "ok"];
    }

    private function error($id) {
        $order = $this->getOrder($id);
        if($order == null) {
            return ["error" => "order not found"];
        }

        $order->setStatus(Order::STATE_CANCELED);
        $order->setState(Order::STATE_CANCELED);

        $order->save();

        return ["status" => "ok"];
    }

    private function refund($id) {
        $order = $this->getOrder($id);
        if($order == null) {
            return ["error" => "order not found"];
        }
        //TODO: Не ну а шо я могу сделать, если такого нет в магенто
        return ["status" => "ok"];
    }

    private function getSignature($key, $status, $id) {
        return md5($id.$status.$key);
    }

    private function getOrder($id) {
        return $this->order->load($id);
    }

    private function getResult($data) {
        $result = $this->jsonResultFactory->create();
        $result->setData($data);
        return $result;
    }

    private function setConfig($key, $value, $path = "payment/invoice_payment/") {
        $this->configWriter->save($path.$key, $value);
    }

    private function getConfig($key, $path = "payment/invoice_payment/") {
        return $this->scopeConfig->getValue("$path$key", "websites");
    }

    private function log($log) {
        $fp = fopen('invoice_payment.log', 'a+');
        fwrite($fp, $log);
        fclose($fp);
    }
}