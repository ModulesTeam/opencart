<?php

namespace Mundipagg\Integrity;

interface OrderInfoInterface
{
    public function loadOrder($id);

    public function getOrder();

    public function getOrderInfo();

    public function getOrderHistory();

    public function getOrderCharges();

    public function getOrderInvoices();
}

