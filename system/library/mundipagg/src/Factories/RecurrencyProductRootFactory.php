<?php

namespace Mundipagg\Factories;

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Aggregates\RecurrencyProduct\RecurrencySubproductValueObject;
use Mundipagg\Aggregates\Template\PlanStatusValueObject;

class RecurrencyProductRootFactory
{
    /**
     * @param $jsonData
     * @return RecurrencyProductRoot
     * @throws \Exception
     * @example
     * [
     *  "productId" => null,
     *  "template" => $templateRoot,
     *  "isSingle" => true,
     *  "subProducts" => $subProducts
     * ]
     */
    public function createFromJson($jsonData)
    {
        $data = json_decode(utf8_decode($jsonData));
        $recurrencySubProductValueObjectFactory = new RecurrencySubproductValueObjectFactory();

        $recurrencyProduct = new RecurrencyProductRoot();
        $recurrencyProduct->setSingle($data->isSingle);
        if (isset($data->mundipaggPlanId)) {
            $recurrencyProduct->setMundipaggPlanId($data->mundipaggPlanId);
        }
        if (isset($data->mundipaggPlanStatus)) {
            $recurrencyProduct->setMundipaggPlanStatus(
                new PlanStatusValueObject($data->mundipaggPlanStatus)
            );
        }
        $recurrencyProduct->setProductId($data->productId);
        $recurrencyProduct->setTemplate(
            (new TemplateRootFactory())->createFromJson(json_encode($data->template))
        );

        $planPriceInCents = 0;

        foreach ($data->subProducts as $subProduct) {
            $_subProduct = $recurrencySubProductValueObjectFactory->createFromJson(
                json_encode($subProduct)
            );
            $recurrencyProduct->addSubproduct($_subProduct);
            $planPriceInCents +=
                $subProduct->unit_price_in_cents * $subProduct->quantity
            ;
        }
        $recurrencyProduct->setPrice($planPriceInCents);

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
            ->setPrice($dbData['price'])
            ->setTemplate((new TemplateRootFactory())->createFromJson(
                $dbData['template_snapshot']
            ));

        if (!empty($dbData['mundipagg_plan_status'])) {
            $productRoot->setMundipaggPlanStatus(
                new PlanStatusValueObject($dbData['mundipagg_plan_status'])
            );
        }

        $subPproductIds = explode(',' ,$dbData['sub_product_id']);
        $subQuantities = explode(',' ,$dbData['sub_quantity']);
        $subCycles = explode(',' ,$dbData['sub_cycles']);
        $subCycleTypes = explode(',' ,$dbData['sub_cycle_type']);
        $subUnitPriceInCents = explode(',' ,$dbData['sub_unit_price_in_cents']);

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
                ->setUnitPriceInCents($subUnitPriceInCents[$index])
            );
        }

        return $productRoot;
    }
}