<?php

namespace Mundipagg\Controller;

use Mundipagg\Model\Order as MundipaggOrder;
use Mundipagg\Helper\Common as CommonHelper;

class Charges
{
    private $openCart;
    private $possibleActions = ['capture', 'cancel'];

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
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

        $data['text_order'] = sprintf('Order (#%s)', $order_id);
        $data['column_product'] = 'Product';
        $data['column_model'] = 'Model';
        $data['column_quantity'] = 'Quantity';
        $data['column_price'] = 'Unit Price';
        $data['column_total'] = 'Total';
        $data['charges'] = $this->openCart->getChargesData($order_info, $status);
        $data['products'] = $this->openCart->getDataProducts($order_info);
        $data['vouchers'] = $this->openCart->getVoucherData($order_info);
        $data['totals'] = $this->openCart->getTotalsData($order_info);
        $data['header'] = $this->openCart->load->controller('common/header');
        $data['column_left'] = $this->openCart->load->controller('common/column_left');
        $data['footer'] = $this->openCart->load->controller('common/footer');
        $data['heading_title'] = "Preview $status charge";
        $data['cancel_capture_modal_template'] =
            'extension/payment/mundipagg/cancel_capture_modal.twig';
        $data['mundipagg_loader'] = 'extension/payment/mundipagg/loader.twig';

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

            $data[$key] = $charge;
            $data[$key]['actions'] = $this->possibleActions;
        }

        return $data;
    }

    public function getChargeInformation($chargeId)
    {
        return 1;
    }
}
