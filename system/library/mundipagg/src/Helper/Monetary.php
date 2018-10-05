<?php

namespace Mundipagg\Helper;

class Monetary
{
    public function monetaryToCents($amount)
    {
        return preg_replace('/[^0-9]/', '', $amount);
    }

    public function centsToMonetary(
        $amount,
        $symbol = '',
        $decimalPoint = '.',
        $thousandsSep = ''
    )
    {
        $value = number_format(
            $amount / 100,
            '2',
            $decimalPoint,
            $thousandsSep
        );

        return $symbol . $value;
    }
}