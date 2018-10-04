<?php

namespace Mundipagg\Helper;

class Monetary
{
    public function monetaryToCents($amount)
    {
        $amount = number_format($amount, '2');
        return preg_replace('/[^0-9]/', '', $amount);
    }
}