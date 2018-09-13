<?php

namespace Mundipagg\Controller;

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

use Mundipagg\Enum\WebHookEnum;
use Mundipagg\Log;
use Mundipagg\LogMessages;
use Mundipagg\Model\WebHook as WebHookModel;
use Mundipagg\Enum\OrderstatusEnum;
use Mundipagg\Model\Charge as MundipaggChargeModel;

class WebHook
{
    private $action;
    private $type;
    private $id;
    private $account;
    private $data;
    private $openCart;
    private $webHook;
    private $model;
    private $mPOrderId;

    public function __construct($opencart, $webHook)
    {
        $this->webHook = json_decode($webHook, true);
        $this->openCart = $opencart;

        $this->getWebHookBasicInfo($this->webHook);
        $this->model = new WebHookModel($opencart);
    }

    public function load()
    {
        $this->openCart->load->model('extension/payment/mundipagg_webhook_logging');
        $this->openCart->load->model('setting/setting');
        $this->openCart->load->language('extension/payment/mundipagg');
        $this->openCart->load->model('localisation/currency');
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function getData()
    {
        return $this->data;
    }

    private function getWebHookBasicInfo($webHook)
    {
        $webHookType = explode('.', $webHook['type']);

        $this->type = $webHookType[0];
        $this->action = $webHookType[1];
        $this->id = $webHook['id'];
        $this->account = $webHook['account'];
        $this->data = $webHook['data'];

        $this->mPOrderId = $this->data['id'];
    }

    public function updateStatus()
    {
        if (!$this->isValidWebHook()) {
            Log::create()
                ->error(LogMessages::UNKNOWN_WEBHOOK_TYPE, __METHOD__)
                ->withWebHook($this->webHook)
                ->withWebHookId($this->id)
                ->withOrderId($this->data['id']);

            return false;
        }

        switch ($this->type) {
            case WebHookEnum::TYPE_ORDER:
                return $this->updateOrder();
            case WebHookEnum::TYPE_CHARGE:
                return $this->updateCharge();
            default:
                Log::create()
                    ->error(LogMessages::UNKNOWN_WEBHOOK_TYPE, __METHOD__)
                    ->withWebHookId($this->id)
                    ->withWebHook($this->webHook);
                return false;
        }
    }

    // --------------------------- ORDER --------------------------------------------
    private function updateOrder()
    {
        $this->openCart->load->language('extension/payment/mundipagg');
        $language = $this->openCart->load->language('extension/payment/mundipagg');
        $language = $language['order_history_update'];
        $amount = null;

        switch ($this->action) {
            case WebHookEnum::ACTION_PAID:
                $openCartStatus = OrderstatusEnum::ORDER_PROCESSING_ID;
                $amount = $this->data['amount'];
                $i18nMessage = $language['orderPaid'];
                break;
            case WebHookEnum::ACTION_CANCELED:
                $openCartStatus = OrderstatusEnum::ORDER_CANCELED_ID;
                $i18nMessage = $language['orderCanceled'];
                break;
            case WebHookEnum::ACTION_PAYMENT_FAILED:
                $openCartStatus = OrderstatusEnum::ORDER_CANCELED_ID;
                $i18nMessage = $language['orderCanceled'];
                break;
            case WebHookEnum::ACTION_CREATED:
                Log::create()
                    ->info(LogMessages::CREATE_WEBHOOK_RECEIVED, __METHOD__)
                    ->withWebHook($this->webHook)
                    ->withWebHookId($this->id)
                    ->withWebHookStatus($this->action);
                return true;
            default:
                Log::create()
                    ->error(LogMessages::INVALID_WEBHOOK_ORDER_STATUS, __METHOD__)
                    ->withWebHook($this->webHook)
                    ->withWebHookId($this->id)
                    ->withWebHookStatus($this->action);
                return false;
        }

        return $this->setOrderStatus($this->mPOrderId, $openCartStatus, $i18nMessage, $amount);
    }

    private function setOrderStatus($mPOrderId, $status, $message, $amount = null)
    {
        $this->load();

        $orderId = $this->model->getOpenCartOrderIdFromMundiPaggOrderId($mPOrderId);
        $comment = $this->getOrderComment($message, $amount);

        $this->addOrderHistory($orderId, $status, $comment, 0);
        $this->model->setOrderStatus($orderId, $status);

        return true;
    }

    private function getOrderComment($message, $amount = null)
    {
        return $amount ? $message . ' ' . $amount : $message;
    }
    // ------------------------------------------------------------------------------

    // --------------------------- CHARGE -------------------------------------------
    private function updateCharge()
    {
        $this->openCart->load->language('extension/payment/mundipagg');
        $language = $this->openCart->load->language('extension/payment/mundipagg');
        $language = $language['order_history_update'];
        $mPOrderId = $this->data['order']['id'];
        $amount = 0;
        $field = "";

        switch ($this->action) {
            case WebHookEnum::ACTION_PAID:
                $i18Message = $language['chargePaid'];
                $field = 'paid_amount';
                $amount = $this->data['amount'];
                break;
            case WebHookEnum::ACTION_OVERPAID:
                $i18Message = $language['chargeOverPaid'];
                $field = 'paid_amount';
                $amount = $this->data['paid_amount'];
                break;
            case WebHookEnum::ACTION_UNDERPAID:
                $i18Message = $language['chargeUnderPaid'];
                $field = 'paid_amount';
                $amount = $this->data['paid_amount'];
                break;
            case WebHookEnum::ACTION_REFUNDED:
                $i18Message = $language['chargeRefunded'];
                $amount = $this->data['canceled_amount'];
                $field = 'canceled_amount';
                break;
            case WebHookEnum::ACTION_PAYMENT_FAILED:
                $i18Message = $language['chargePaymentFailed'];
                break;
            case WebHookEnum::ACTION_PARTIAL_CANCELED:
                $i18Message = $language['chargePartialCanceled'];
                $amount = $this->data['canceled_amount'];
                $field = 'canceled_amount';
                break;
            case WebHookEnum::ACTION_CREATED:
                Log::create()
                    ->info(LogMessages::CREATE_WEBHOOK_RECEIVED, __METHOD__)
                    ->withWebHook($this->webHook)
                    ->withWebHookId($this->id)
                    ->withWebHookStatus($this->action);
                return true;
            default:
                Log::create()
                    ->error(LogMessages::INVALID_WEBHOOK_CHARGE_STATUS, __METHOD__)
                    ->withWebHook($this->webHook)
                    ->withWebHookId($this->id)
                    ->withWebHookStatus($this->action);
                return false;
        }

        $charges = new MundipaggChargeModel($this->openCart);

        $charges->updateAmount(
            $field,
            $amount,
            $this->data['status'],
            $this->data['id'],
            $this->data['code']
        );

        return $this->setChargeStatus($mPOrderId, $i18Message, $amount);
    }

    private function setChargeStatus($mPOrderId, $message, $amount)
    {
        $orderId = $this->model->getOpenCartOrderId($mPOrderId);

        $comment = $this->getChargeComment($message, $amount);
        $this->addOrderHistory($orderId, OrderstatusEnum::ORDER_PROCESSING_ID, $comment, 0);

        return true;
    }

    private function getChargeComment($message, $amount)
    {
        return $message .
            $this->moneyFormat($amount) .
            '/' .
            $this->moneyFormat($this->data['amount']);
    }
    // ------------------------------------------------------------------------------

    public function addOrderHistory($orderId, $orderStatus, $comment, $sendEmail)
    {
        $this->openCart->load->model('checkout/order');
        $this->openCart->model_checkout_order->addOrderHistory(
            $orderId,
            $orderStatus,
            $comment,
            $sendEmail,
            false
        );
    }

    private function moneyFormat($amountInCents)
    {
        $currency = $this->getCurrency();
        return $currency['symbol_left'] . number_format($amountInCents * 0.01, 2) . $currency['symbol_right'];
    }

    private function getCurrency()
    {
        $this->openCart->load->model('localisation/currency');
        $currencyModel = $this->openCart->model_localisation_currency;

        return $currencyModel->getCurrencyByCode($this->openCart->config->get('config_currency'));
    }

    private function isValidWebHook()
    {
        // MundiPagg api is not returning the authorization header yet
        return true;
    }
}
