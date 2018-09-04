<?php

use Mundipagg\Log;
use Mundipagg\LogMessages;

class ModelExtensionPaymentMundipaggOrderBoletoInfo extends Model
{
    public function getBoletoLinks($orderId)
    {
        $sql = "SELECT link FROM
               `". DB_PREFIX ."mundipagg_order_boleto_info`
               WHERE `opencart_order_id` = $orderId
                ";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function saveOrderBoletoInfo($orderId, $chargeId, $lineCode, $dueAt, $link)
    {
        $sql = "INSERT INTO `". DB_PREFIX ."mundipagg_order_boleto_info`
            (opencart_order_id,charge_id,line_code,due_at, link) 
            VALUES($orderId,'$chargeId','$lineCode','$dueAt', '$link')"
            ;
        try {
            $this->db->query($sql);
        } catch (Exception $e) {
            Log::create()
                ->error(LogMessages::CANNOT_CREATE_SAVE_BOLETO_LINK, __METHOD__)
                ->withException($e)
                ->withOrderId($orderId)
                ->withLineNumber(__LINE__)
                ->withQuery($sql);
        }
    }
}
