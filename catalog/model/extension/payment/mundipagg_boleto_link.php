<?php

use Mundipagg\Log;
use Mundipagg\LogMessages;

class ModelExtensionPaymentMundipaggBoletoLink extends Model
{
    public function getBoletoLinks($orderId)
    {
        $sql = "SELECT link FROM
               `". DB_PREFIX ."mundipagg_boleto_link`
               WHERE `opencart_order_id` = $orderId
                ";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function saveBoletoLink($orderId, $link)
    {
        $sql = "INSERT INTO `". DB_PREFIX ."mundipagg_boleto_link`
            (opencart_order_id, link) VALUES($orderId, '$link')
            ";
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
