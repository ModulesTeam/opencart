<?php

namespace Mundipagg\Factories;

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;

class RecurrencyProductRootFactory
{
    public function createFromJson($jsonData)
    {
        $data = json_decode($jsonData);
        $recurrencySubProductValueObjectFactory = new RecurrencySubproductValueObjectFactory();

        $recurrencyProduct = new RecurrencyProductRoot();
        $recurrencyProduct->setSingle($data->isSingle);
        $recurrencyProduct->setMundipaggPlanId($data->mundipaggPlanId);
        $recurrencyProduct->setProductId($data->productId);
        $recurrencyProduct->setTemplate(
            (new TemplateRootFactory())->createFromJson(json_encode($data->template))
        );

        foreach ($data->subProducts as $subProduct) {
            $_subProduct = $recurrencySubProductValueObjectFactory->createFromJson(
                json_encode($subProduct)
            );
            $recurrencyProduct->addSubproduct($_subProduct);
        }

        return $recurrencyProduct;
    }
}