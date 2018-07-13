<?php
namespace Mundipagg\Model;

use Mundipagg\Log;
use Mundipagg\LogMessages;

class Charge
{
    private $openCart;
    private $tableName;

    public function __construct($openCart)
    {
        $this->tableName = DB_PREFIX . "mundipagg_charge";
        $this->openCart = $openCart;
    }

    /**
     * @param $field
     * @param $amount
     * @param $status
     * @param $chargeId
     * @param $OrderId
     */
    public function updateAmount(
        $field,
        $amount,
        $status,
        $chargeId,
        $OrderId
    )
    {
        $sql = "
            UPDATE " . $this->tableName . " SET
            status = '" . $status . "',
            " . $field . " =  '". $amount . "'
            where charge_id = '" . $chargeId . "'
        ";

        try {
            $this->openCart->db->query($sql);
        } catch (\Exception $exc) {
            Log::create()
                ->error(LogMessages::CANNOT_UPDATE_ORDER_AMOUNT, __METHOD__)
                ->withOrderId($OrderId);
        }
    }
}