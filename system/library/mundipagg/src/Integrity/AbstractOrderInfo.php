<?php

namespace Mundipagg\Integrity;

abstract class AbstractOrderInfo implements OrderInfoInterface
{
    protected $order;

    public function loadOrder($id) {
        $this->order = $this->_loadOrder($id);
    }

    public function getOrder() {
        return $this->order;
    }

    public function getOrderHistory()
    {
        if ($this->order !== null ) {
            return $this->_getOrderHistory();
        }
        return null;
    }

    public function getOrderCharges()
    {
        if ($this->order !== null ) {
            return $this->_getOrderCharges();
        }
        return null;
    }

    public function getOrderInvoices()
    {
        if ($this->order !== null ) {
            return $this->_getOrderInvoices();
        }
        return null;
    }

    abstract protected function _loadOrder($id);

    abstract protected function _getOrderHistory();

    abstract protected function _getOrderCharges();

    abstract protected function _getOrderInvoices();
}