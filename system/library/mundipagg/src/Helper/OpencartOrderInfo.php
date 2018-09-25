<?php

namespace Mundipagg\Helper;

use Mundipagg\Integrity\AbstractOrderInfo;
use Mundipagg\Model\Order;
use Mundipagg\Repositories\Decorators\OpencartPlatformDatabaseDecorator;
use Mundipagg\Repositories\RecurrencyProductRepository;

class OpencartOrderInfo extends AbstractOrderInfo
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    protected function _loadOrder($id)
    {
        $this->openCart->load->model('checkout/order');

        return $this->openCart->model_checkout_order->getOrder($id);
    }

    protected function _getOrderHistory()
    {
        $this->openCart->load->model('account/order');

        return $this->openCart->model_account_order
            ->getOrderHistories($this->getOrder()['order_id']);
    }

    protected function _getOrderCharges()
    {
        $order = new Order($this->openCart);

        return $order->getCharge($this->getOrder()['order_id']);
    }

    protected function _getOrderInvoices()
    {
        return [
            'invoice_no' => $this->getOrder()['invoice_no'],
            'invoice_prefix' => $this->getOrder()['invoice_prefix']
        ];
    }

    protected function _getOrderInfo()
    {
        return $this->getOrder();
    }

    public function getRecurrenceProduct($cart)
    {
        //filter products
        $items = $cart->getProducts();

        $plans = [];
        $recurrenceProductRepo = new RecurrencyProductRepository(
            new OpencartPlatformDatabaseDecorator($this->openCart->db)
        );

        foreach ($items as $item) {
            $product = $recurrenceProductRepo->getByProductId($item['product_id']);
            if ($product !== null) {
                $plans[] = $product;
            }
        }

        if (count($plans) == 1 && count($items) == 1) {
            return $plans[0];
        }

        return null;
    }
}