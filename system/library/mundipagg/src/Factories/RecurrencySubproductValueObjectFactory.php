<?php

namespace Mundipagg\Factories;

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencySubproductValueObject;

class RecurrencySubproductValueObjectFactory
{
    public function createFromJson($jsonData)
    {
        $data = json_decode(utf8_decode($jsonData));

        $recurrencySubproduct = new RecurrencySubproductValueObject();
        $recurrencySubproduct->setProductId($data->productId);
        $recurrencySubproduct->setCycles($data->cycles);
        $recurrencySubproduct->setCycleType($data->cycleType);
        $recurrencySubproduct->setQuantity($data->quantity);
        $recurrencySubproduct->setUnitPriceInCents($data->unit_price_in_cents);

        return $recurrencySubproduct;
    }
}