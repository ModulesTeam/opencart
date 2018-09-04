<?php
use Mundipagg\Log;
use Mundipagg\LogMessages;

class ModelExtensionPaymentMundipaggOrderCreditcardInfo extends Model
{
    public function saveOrderCreditcardInfo($orderId, $chargeId, $holderName, $brand, $lastFourDigits, $installments)
    {
        $sql = "INSERT INTO `". DB_PREFIX ."mundipagg_order_creditcard_info`
            (opencart_order_id,charge_id,holder_name,brand, last_four_digits,installments)" .
            " VALUES($orderId,'$chargeId','$holderName','$brand', $lastFourDigits,$installments)";
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