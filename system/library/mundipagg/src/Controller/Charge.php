<?php

namespace Mundipagg\Controller;

use MundiAPILib\MundiAPIClient;
use MundiAPILib\Models\CreateCancelChargeRequest;
use Mundipagg\Settings\General as GeneralSettings;
use Mundipagg\Model\Charge as MundipaggChargeModel;
use Mundipagg\Log;
use Mundipagg\LogMessages;

class Charge
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
        $this->openCart->load->language('extension/payment/mundipagg');
    }

    public function updateCharge($chargeData, $chargeRequest)
    {
        $chargeController = $this->getChargeController();
        $action = 'capture';

        if ($chargeRequest instanceof CreateCancelChargeRequest) {
            $action = 'cancel';
        }
        $method = $action . 'Charge';

        $chargeRequest->amount = $chargeData['selectedAmount'];

        //LOG REQUEST

        try {
            $response =
                $chargeController
                    ->$method(
                        $chargeData['charge_id'],
                        $chargeRequest
                    )
            ;

            //LOG RESPONSE

            $this->saveChargeUpdate($response, $action);
            //$this->updateOrderStatus($response, $action);

            $text = $this->openCart->language->get('charge_screen');

            return $text['charge_action_success'];

        } catch (\Exception $e) {
            //LOG
            return $e->getMessage();
        }
    }

    protected function getOrderController()
    {
        return $this->getMundiPaggApiClient()->getOrders();
    }

    protected function getChargeController()
    {
        return $this->getMundiPaggApiClient()->getCharges();
    }

    protected function getMundiPaggApiClient()
    {
        $generalConfig = new GeneralSettings($this->openCart);

        $secretKey = $generalConfig->getSecretKey();
        $password = $generalConfig->getPassword();

        return new MundiAPIClient($secretKey, $password);
    }

    protected function saveChargeUpdate($response, $action)
    {
        $order = new MundipaggChargeModel($this->openCart);
        $field = 'paid_amount';

        if ($action === 'cancel') {
            $field = 'canceled_amount';
        }
        $amount = $response->lastTransaction->amount;

        $order->updateAmount(
            $field,
            $amount,
            $response->status,
            $response->id,
            $response->code
        );
    }
}