<?php

namespace Mundipagg\Controller;

use Mundipagg\Model\Order as MundipaggOrder;
use Mundipagg\Helper\Common as CommonHelper;
use MundiAPILib\Models\CreateCancelChargeRequest;
use MundiAPILib\Models\CreateCaptureChargeRequest;
use Mundipagg\Controller\Charge as MundipaggCharge;

class Charges
{
    private $openCart;
    private $charge;
    private $actions = ['capture', 'cancel'];

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
        $this->openCart->load->language('extension/payment/mundipagg');
        $this->charge = new MundipaggCharge($openCart);
    }

    /**
     * @return mixed
     */
    public function getPreviewHtml()
    {
        if (empty($this->openCart->request->get['order_id'])) {
            return $this->openCart->redirect('sale/order');
        }

        $this->openCart->load->model('sale/order');

        $order_id = $this->openCart->request->get['order_id'];
        $order_info = $this->openCart->model_sale_order->getOrder($order_id);

        $status = '';
        if (isset($this->openCart->request->get['status'])) {
            $status = $this->openCart->request->get['status'];
        }

        $data['chargeModalInformationUrl'] = $this->openCart->url->link(
            'extension/payment/mundipagg/getChargeModalInformation',
            'user_token=' . $this->openCart->session->data['user_token'],
            true);

        $data['performChargeActionUrl'] = $this->openCart->url->link(
            'extension/payment/mundipagg/performChargeAction',
            'user_token=' . $this->openCart->session->data['user_token'],
            true);

        $data['cancel_capture_modal_template'] =
            'extension/payment/mundipagg/cancel_capture_modal.twig';

        $data['mundipagg_loader'] = 'extension/payment/mundipagg/loader.twig';

        $data['text'] = $this->openCart->language->get('charge_screen');

        $data['order_id'] = $order_id;
        $data['charges'] = $this->openCart->getChargesData($order_info, $status);
        $data['products'] = $this->openCart->getDataProducts($order_info);
        $data['vouchers'] = $this->openCart->getVoucherData($order_info);
        $data['totals'] = $this->openCart->getTotalsData($order_info);
        $data['header'] = $this->openCart->load->controller('common/header');
        $data['column_left'] = $this->openCart->load->controller('common/column_left');
        $data['footer'] = $this->openCart->load->controller('common/footer');
        $data['heading_title'] = "Preview $status charge";

        return $this->openCart->load->view(
            'extension/payment/mundipagg_previewChangeCharge',
            $data
        );
    }

    public function getData($order_info, $status)
    {
        $data = [];
        $orderId = $this->openCart->request->get['order_id'];
        $order = new MundipaggOrder($this->openCart);
        $helper = new CommonHelper($this->openCart);
        $charges = $order->getCharge($orderId);

        foreach ($charges->rows as $key => $charge) {
            $charge['amount'] =
                $helper->currencyFormat($charge['amount'] / 100, $order_info);
            $charge['canceled_amount'] =
                $helper->currencyFormat($charge['canceled_amount'] / 100, $order_info);
            $charge['paid_amount'] =
                $helper->currencyFormat($charge['paid_amount'] / 100, $order_info);

            $data[$key] = $charge;

            $data[$key]['actions'] = $this->getPossibleActions($charge);
        }

        return $data;
    }

    public function getChargeInformation($orderId, $chargeId)
    {
        $mundipaggOrder = new MundipaggOrder($this->openCart);
        $order = $mundipaggOrder->getOrder($orderId);
        $charge = $mundipaggOrder->getCharge($orderId, $chargeId)->row;
        $text = $this->openCart->language->get('charge_screen');

        if ($charge) {
            $formatted_amount =
                number_format(
                    $charge['amount'] /100,
                    '2',
                    '.',
                    ''
                );
            $charge['formatted_amount'] = $formatted_amount;
            $charge['currency_symbol'] = $order['symbol_left'];
            $charge['text'] = $text;

            return json_encode($charge);
        }
    }

    private function getPossibleActions($charge)
    {
        if ($charge['status'] == 'canceled') {
            return null;
        }

        if($charge['payment_method'] == 'boleto') {
            return 'cancel';
        }

        return $this->actions;
    }

    public function performChargeAction(
        $chargeId,
        $orderId,
        $action,
        $selectedAmount
    )
    {
        $helper = new CommonHelper($this->openCart);
        $method = $helper->fromSnakeToCamel($action);

        if(method_exists($this, $method)) {
            $mundipaggOrder = new MundipaggOrder($this->openCart);
            $charge = $mundipaggOrder->getCharge($orderId, $chargeId)->row;

            $result['msg'] = $this->$method($charge, $selectedAmount * 100);
        }
        $result['charge_id'] = $chargeId;

        return json_encode($result);
    }

    public function partialCapture($chargeData, $selectedAmount) {
        $chargeData['selectedAmount'] = $selectedAmount;

        return
            $this
            ->charge
            ->updateCharge($chargeData, new CreateCaptureChargeRequest());
    }

    public function partialCancel($chargeData, $selectedAmount) {
        $chargeData['selectedAmount'] = $selectedAmount;

        return
            $this
            ->charge
            ->updateCharge($chargeData, new CreateCancelChargeRequest());
    }

    public function totalCapture($chargeData) {
        $chargeData['selectedAmount'] = null;

        return
            $this
            ->charge
            ->updateCharge($chargeData, new CreateCaptureChargeRequest());
    }

    public function totalCancel($chargeData) {
        $chargeData['selectedAmount'] = null;

        return
            $this
            ->charge
            ->updateCharge($chargeData, new CreateCancelChargeRequest());
    }
}
