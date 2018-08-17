<?php

namespace Mundipagg\Helper;

use Mundipagg\Integrity\AbstractOrderInfo;

class OpencartOrderInfo extends AbstractOrderInfo
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    protected function _loadOrder($id)
    {
        // TODO: Implement _loadOrder() method.
    }

    protected function _getOrderHistory()
    {
        // TODO: Implement _getOrderHistory() method.
    }

    protected function _getOrderCharges()
    {
        // TODO: Implement _getOrderCharges() method.
    }

    protected function _getOrderInvoices()
    {
        // TODO: Implement _getOrderInvoices() method.
    }
}