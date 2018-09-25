<?php
namespace Mundipagg\Model;

use Mundipagg\Log;
use Mundipagg\LogMessages;
use Mundipagg\Settings\CreditCard;

class Installments
{
    private $openCartModel;

    public function __construct($openCartModel)
    {
        $this->openCartModel = $openCartModel;
    }

    public function getInstallmentsFor($brand, $total)
    {
        if (!$this->doesBrandExist($brand) || !$this->isBrandEnabled($brand)) {
            return '';
        }

        if (
            $this->doesBrandExist('default') &&
            $this->isBrandEnabled('default')
        ) {
          $brand = 'Default';
        }

        $installmentsRules = $this->getInstallmentsRulesPerBrand($brand)[0];

        $maxInstallments = $installmentsRules['installments_up_to'];
        $maxInstallmentsWithoutInterest = $installmentsRules['installments_without_interest'];
        $interest = $installmentsRules['interest'];
        $incrementalInterest = $installmentsRules['incremental_interest'];

        $installmentsWithoutInterest = $this->getInstallmentsWithoutInterest(
            $total,
            $maxInstallmentsWithoutInterest
        );

        $installmentsWithInterest = $this->getInstallmentsWithInterest(
            $total,
            $maxInstallmentsWithoutInterest,
            $maxInstallments,
            $interest,
            $incrementalInterest
        );

        $installments = array_merge($installmentsWithoutInterest, $installmentsWithInterest);

        $installments = array_filter($installments, function($installment)
            use ($maxInstallments) {
            return $installment['times'] <= $maxInstallments;
        });

        return $installments;
    }

    private function getInstallmentsRulesPerBrand($brandName)
    {
        $fields = '
            brand_name,
            is_enabled, 
            installments_up_to,
            installments_without_interest,
            interest,
            incremental_interest
        ';
        $selectStatement = 'SELECT ' . $fields . ' FROM';
        $tableName = DB_PREFIX .'mundipagg_payments';
        $condition = "WHERE brand_name = '" . $brandName . "'";

        $sql = $selectStatement . ' `' . $tableName . '` ' . $condition;
        $query = $this->openCartModel->query($sql);

        return $query->rows;
    }

    private function doesBrandExist($brandName)
    {
        $selectStatement = 'SELECT brand_name FROM';
        $tableName = DB_PREFIX .'mundipagg_payments';
        $condition = "WHERE brand_name = '" . $brandName . "'";

        $sql = $selectStatement . ' `' . $tableName . '` ' . $condition;
        $query = $this->openCartModel->query($sql);

        return $query->num_rows === 1;
    }

    private function isBrandEnabled($brandName)
    {
        $selectStatement = 'SELECT is_enabled FROM';
        $tableName = DB_PREFIX .'mundipagg_payments';
        $condition = "WHERE brand_name = '" . $brandName . "'";

        $sql = $selectStatement . ' `' . $tableName . '` ' . $condition;
        $query = $this->openCartModel->query($sql);

        return $query->row['is_enabled'] !== '0';
    }

    private function getInstallmentsWithoutInterest($total, $max)
    {
        $installments = [];

        for ($i = 0; $i < $max; $i++) {
            $amount = $total / ($i + 1);
            $amount = number_format($amount,2,'.','.');

            $installments[] = [
                'amount' => floatval($amount),
                'times' => $i + 1,
                'interest' => 0
            ];
        }
        return $installments;
    }

    private function getInstallmentsWithInterest($total, $maxWithout, $max, $interest, $increment = 0)
    {
        $installments = [];

        for ($i = $maxWithout; $i < $max; $i++) {
            $interestAmount = $total * ($interest / 100);

            $amount = ($total + $interestAmount) / ($i + 1);
            $amount = number_format($amount,2,'.','.');

            $totalWithInterest = $total + $interestAmount;
            $totalWithInterest = number_format($totalWithInterest,2,'.','.');

            $installments[] = [
                'amount' => floatval($amount),
                'times' => $i + 1,
                'interest' => number_format($interest,2,'.','.'),
                'total' => $totalWithInterest
            ];
            $interest += $increment;
        }
        return $installments;
    }
}
