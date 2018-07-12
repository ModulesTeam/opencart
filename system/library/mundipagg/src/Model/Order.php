<?php
namespace Mundipagg\Model;

use Mundipagg\Log;

class Order
{
    private $openCart;
    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    public function saveCharge(array $data)
    {
        $charge = $this->getCharge($data['opencart_id'], $data['charge_id']);
        if ($charge->num_rows) {
            $this->updateCharge($data);
        } else {
            $this->insertCharge($data);
        }
    }

    public function getOrders($data, $fields)
    {
        $where = [];
        if (isset($data['ids'])) {
            $where[]= 'order_id IN(' . implode(',', $data['ids']) . ')';
        }
        if (isset($data['order_status_id'])) {
            $where[]= 'order_status_id IN(' . implode(',', $data['order_status_id']) . ')';
        }
        if ($where) {
            return $this->openCart->db->query(
                'SELECT ' . implode(",\n         ", $fields) .
                '  FROM `' . DB_PREFIX . "order`\n" .
                ' WHERE ' . implode(' AND ', $where)
            );
        }
    }

    public function getCharge($opencart_id, $charge_id = null)
    {
        $query =  "SELECT charge.charge_id as charge_id, ".
            "       charge.payment_method as payment_method, ".
            "       charge.status as status, ".
            "       charge.paid_amount as paid_amount, ".
            "       charge.amount as amount, ".
            "       boleto_info.link as boleto_link, ".
            "       boleto_info.line_code as boleto_line_code, ".
            "       boleto_info.due_at as boleto_due_at, ".
            "       creditcard_info.holder_name as creditcard_holder_name, ".
            "       creditcard_info.brand as creditcard_brand, ".
            "       creditcard_info.last_four_digits as creditcard_last_four_digits, ".
            "       creditcard_info.installments as creditcard_installments, ".
            "       charge.opencart_id AS order_id ".
            '  FROM `' . DB_PREFIX . "mundipagg_charge` as charge ".
            ' LEFT JOIN `' . DB_PREFIX . "mundipagg_order_boleto_info` as boleto_info " .
            "ON charge.charge_id = boleto_info.charge_id " .
            ' LEFT JOIN `' . DB_PREFIX . "mundipagg_order_creditcard_info` as creditcard_info " .
            "ON charge.charge_id = creditcard_info.charge_id " .
            ' WHERE charge.opencart_id = ' . $opencart_id .
            ($charge_id ? ' AND charge.charge_id = "' . $charge_id . '"' : '');

        $charge = $this->openCart->db->query($query);

        return $charge;
    }

    private function updateCharge(array $data)
    {
        $query = 'UPDATE `' . DB_PREFIX . 'mundipagg_charge` SET ';
        $fields = array();
        foreach ($data as $key => $value) {
            $fields[]= ' ' . $key . ' = "' . $value . '"';
        }
        $query.= implode(', ', $fields) .
            ' WHERE opencart_id = ' . $data['opencart_id'] .
            '   AND charge_id = "' . $data['charge_id'] . '"';
        $this->openCart->db->query($query);
    }

    private function insertCharge(array $data)
    {
        $sql = 'INSERT INTO `' . DB_PREFIX . 'mundipagg_charge` ' .
            '(' . implode(',', array_keys($data)) . ') ' .
            'VALUES ("' . implode('", "', $data) . '"'.
            ');'
        ;
        $this->openCart->db->query($sql);
    }

    public function updateOrderStatus($order_id, $order_status_id)
    {
        $this->openCart->db->query(
            "UPDATE `" . DB_PREFIX . "order`
                        SET order_status_id = " . $order_status_id . "
                        WHERE order_id = ". $order_id
        );
    }

    public function updateAmount($orderId, $amount)
    {
        $sql = "UPDATE `" . DB_PREFIX . "order` " .
            "set `total` = '" . $amount . "' " .
            "WHERE `order_id` = '" . $orderId . "'";

        try {
            $this->openCart->db->query($sql);
        } catch (\Exception $e) {
            Log::create()
                ->error(LogMessages::UNABLE_TO_UPDATE_ORDER_AMOUNT, __METHOD__)
                ->withOrderId($orderId)
                ->withLineNumber(__LINE__)
                ->withQuery($sql);
        }
    }

    public function updateAmountInOrderTotals($orderId, $orderAmount)
    {
        $sql = "UPDATE `" . DB_PREFIX . "order_total` " .
            "set `value` = '" . $orderAmount . "' " .
            "WHERE `order_id` = '" . $orderId . "' " .
            "AND code = 'total' ";

        try {
            $this->openCart->db->query($sql);
        } catch (\Exception $e) {
            Log::create()
                ->error(LogMessages::UNABLE_TO_UPDATE_ORDER_AMOUNT, __METHOD__)
                ->withOrderId($orderId)
                ->withLineNumber(__LINE__)
                ->withQuery($sql);
        }
    }

    public function insertInterestInOrderTotals($orderId, $interestAmount)
    {
        $sql = "INSERT INTO `" . DB_PREFIX . "order_total` " .
            "(" .
            "`order_id`," .
            " `code`," .
            "`title`," .
            "`value`," .
            "`sort_order`" .
            ")".
            " VALUES (" .
            "'" . $orderId . "'," .
            "'mundipagg_interest'," .
            "'Juros'," .
            "'" . $interestAmount . "'," .
            "'3'" .
            ")";

        try {
            $this->openCart->db->query($sql);
        } catch (\Exception $e) {
            Log::create()
                ->error(LogMessages::UNABLE_TO_UPDATE_ORDER_AMOUNT, __METHOD__)
                ->withOrderId($orderId)
                ->withLineNumber(__LINE__)
                ->withQuery($sql);
        }
    }

    public function getOrder($orderId)
    {
        $sql = "
            SELECT A.order_id,
               A.store_url,
               A.customer_id,
               A.firstname,
               A.lastname,
               A.email,
               A.total,
               A.order_status_id,
               A.currency_id,
               B.symbol_left,
               B.symbol_right,
               A.date_added,
               A.date_modified
            FROM " . DB_PREFIX . ".`order` as A
            LEFT JOIN " . DB_PREFIX . ".currency as B
                   ON B.currency_id = A.currency_id
            WHERE A.order_id = " . $orderId;

        $query = $this->openCart->db->query($sql);
        return $query->row;
    }
}
