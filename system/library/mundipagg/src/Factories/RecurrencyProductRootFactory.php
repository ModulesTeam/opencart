<?php

namespace Mundipagg\Factories;

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Aggregates\RecurrencyProduct\RecurrencySubproductValueObject;

class RecurrencyProductRootFactory
{
    public function createFromJson($jsonData)
    {
        $data = json_decode(utf8_decode($jsonData));
        $recurrencySubProductValueObjectFactory = new RecurrencySubproductValueObjectFactory();

        $recurrencyProduct = new RecurrencyProductRoot();
        $recurrencyProduct->setSingle($data->isSingle);
        if (isset($data->mundipaggPlanId)) {
            $recurrencyProduct->setMundipaggPlanId($data->mundipaggPlanId);
        }
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

    public function createFromDBData($dbData)
    {
        $productRoot = new RecurrencyProductRoot();

        $productRoot
            ->setId($dbData['id'])
            ->setDisabled($dbData['is_disabled'])
            ->setSingle($dbData['is_single'])
            ->setMundipaggPlanId($dbData['mundipagg_plan_id'])
            ->setProductId($dbData['product_id'])
            ->setTemplate((new TemplateRootFactory())->createFromJson(
                $dbData['template_snapshot']
            ));

        $subPproductIds = explode(',',$dbData['sub_product_id']);
        $subQuantities = explode(',',$dbData['sub_quantity']);
        $subCycles = explode(',',$dbData['sub_cycles']);
        $subCycleTypes = explode(',',$dbData['sub_cycle_type']);

        foreach ($subPproductIds as $index => $subProductId) {
            if(strlen($subProductId) < 1) {
                continue;
            }
            $productRoot->addSubproduct(
                (new RecurrencySubproductValueObject())
                ->setProductId($subProductId)
                ->setQuantity($subQuantities[$index])
                ->setCycles($subCycles[$index])
                ->setCycleType($subCycleTypes[$index])
            );
        }

        return $productRoot;
    }
}