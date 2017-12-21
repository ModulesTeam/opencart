<?php
namespace Mundipagg\Model;

use Mundipagg\Log;
use Mundipagg\LogMessages;

class SavedCreditcard
{
    private $openCart;
    private $tableName = "mundipagg_creditcard";

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    public function saveCreditcard($mundipaggCustomerId, $cardData, $opencartOrderId)
    {
        $sql =
            "INSERT INTO".
            "`" . DB_PREFIX . $this->tableName . "` " .
            "(
                id,
                mundipagg_customer_id,
                first_six_digits,
                last_four_digits,
                brand,
                holder_name,
                exp_month,
                exp_year
            ) VALUES (".
            "'" . $cardData->id .  "'," .
            "'" . $mundipaggCustomerId . "', " .
            "'', " . // @todo save first six digits
            "'" . $cardData->lastFourDigits . "', " .
            "'" . $cardData->brand . "', " .
            "'" . $cardData->holderName . "', " .
            "'" . $cardData->expMonth . "', " .
            "'" . $cardData->expYear . "'" .
            ")"
        ;

        try {
            $this->openCart->db->query($sql);
        } catch (\Exception $exc) {
            Log::create()
                ->error(LogMessages::CANNOT_SAVE_CREDIT_CARD_DATA, __METHOD__)
                ->withOrderId($opencartOrderId);
        }
    }

    public function creditCardExists($creditCardId)
    {
        $sql =
            "SELECT id FROM " .
            "`" . DB_PREFIX . $this->tableName . "` " .
            "WHERE id = '" . $creditCardId . "'"
        ;

        try {
            $query = $this->openCart->db->query($sql);

            if ($query->num_rows === 1) {
                return true;
            }
            return false;

        } catch (\Exception $exc) {
            Log::create()
                ->error(LogMessages::CANNOT_SAVE_CREDIT_CARD_DATA, __METHOD__);
        }
    }
}