<?php

namespace Mundipagg\Helper;

use MundiAPILib\Models\CreatePhoneRequest;

class Customer
{
    public function createPhoneRequest($rawPhoneNumber)
    {

        $cleanPhone = preg_replace( '/[^0-9]/', '', $rawPhoneNumber);
        $cleanPhone = ltrim($cleanPhone, '0');

        $phoneRequest = new CreatePhoneRequest();
        $phoneRequest->countryCode = '55';
        $phoneRequest->areaCode = substr($cleanPhone, 0, 2);
        $phoneRequest->number = substr($cleanPhone, 2);
        return $phoneRequest;
    }
}