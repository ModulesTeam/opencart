<?php

namespace Mundipagg\Model;

use Mundipagg\Log;
use Mundipagg\LogMessages;

class WebHook
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    public function getOpenCartOrderId($mundiPaggOrderId)
    {
        $sql = 'SELECT `opencart_id` from `' .
            DB_PREFIX . 'mundipagg_order`' .
            'WHERE `mundipagg_id` = "' . $mundiPaggOrderId . '"';


        $result = $this->openCart->db->query($sql);
        return end($result->row);
    }

    public function setOrderStatus($orderId, $statusId)
    {
        try {
            $sql = $this->getUpdateSql($statusId, $orderId);
            $this->openCart->db->query($sql);
        } catch (\Exception $exc) {
            Log::create()
                ->error(LogMessages::CANNOT_SET_ORDER_STATUS, __METHOD__)
                ->withOrderId($orderId);
        }

        return true;
    }

    public function orderExists($orderId)
    {
        $sql = "SELECT opencart_id FROM `".DB_PREFIX."mundipagg_order`
                WHERE mundipagg_id = '".$orderId . "'
                LIMIT 1";
        $query = $this->opencart->db->query($sql);
        return $query->num_rows;
    }

    private function getUpdateSql($status, $orderId)
    {
        return
            "UPDATE `" . DB_PREFIX . "order`
        SET order_status_id = " . $status . "
        WHERE order_id = ". $orderId;
    }

    public function getCurrentOrderStatus($orderId)
    {
        $sql = "
            SELECT order_status_id FROM
            `" . DB_PREFIX . "order`
            WHERE order_id = " . $orderId;
        $query = $this->openCart->db->query($sql);
        if ($query->num_rows > 0) {
            return trim($query->row['order_status_id']);
        }
    }

    public function getOpenCartOrderIdFromMundiPaggOrderId($mPOrderId)
    {
        $sql = 'SELECT `opencart_id` from `' .
            DB_PREFIX . 'mundipagg_order`' .
            'WHERE `mundipagg_id` = "' . $mPOrderId . '"';

        $result = $this->openCart->db->query($sql);
        return end($result->row);
    }
}
