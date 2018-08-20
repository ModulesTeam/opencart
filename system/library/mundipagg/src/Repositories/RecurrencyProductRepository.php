<?php

namespace Mundipagg\Repositories;

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencySubproductValueObject;
use Mundipagg\Aggregates\IAggregateRoot;
use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;

class RecurrencyProductRepository extends AbstractRep
{
    /**
     * @param RecurrencyProductRoot $recurrencyProduct
     * @throws \Exception
     */
    protected function create(IAggregateRoot &$recurrencyProduct)
    {
        $templateId = $recurrencyProduct->getTemplateId();
        $mundipaggPlanId = $recurrencyProduct->getMundipaggPlanId();
        $query = "
            INSERT INTO `" . $this->db->getTable('RECURRENCY_PRODUCT_TABLE') . "` (
                `is_disabled`,
                `is_single`,
                `product_id`,
                `template_snapshot`,
                `template_id`,
                `mundipagg_plan_id`                
            ) VALUES (
                " . ($recurrencyProduct->isDisabled()?1:0) . ",
                " . ($recurrencyProduct->isSingle()?1:0) . ",
                " . $recurrencyProduct->getProductId() . ",
                '" . json_encode($recurrencyProduct->getTemplate()) . "',
                " . ($templateId ? $templateId : 'NULL')  . ",
                " . ($mundipaggPlanId ? "'$mundipaggPlanId'" : 'NULL') . "               
            )
        ";

        $this->db->query($query);
        $recurrencyProduct->setid($this->db->getLastId());

        $this->createSubproducts($recurrencyProduct);
    }

    protected function update(IAggregateRoot &$object)
    {
        // TODO: Implement update() method.
    }

    public function delete(IAggregateRoot $object)
    {
        // TODO: Implement delete() method.
    }

    public function find($objectId)
    {
        // TODO: Implement find() method.
    }

    public function listEntities($limit, $listDisabled)
    {
        // TODO: Implement listEntities() method.
    }

    /**
     * @param RecurrencyProductRoot $recurrencyProduct
     * @throws \Exception
     */
    protected function createSubproducts(IAggregateRoot &$recurrencyProduct)
    {
        $query ="
        INSERT INTO `" . $this->db->getTable('RECURRENCY_SUBPRODUCT_TABLE') . "` (
                `recurrency_product_id`,
                `product_id`,
                `quantity`,
                `cycles`,
                `cycle_type`                                
            ) VALUES 
        ";

        /** @var RecurrencySubproductValueObject $subProduct */
        foreach ($recurrencyProduct->getSubProducts() as $subProduct) {
            $query .= "(
                {$recurrencyProduct->getId()},
                {$subProduct->getProductId()},
                {$subProduct->getQuantity()},
                {$subProduct->getCycles()},
                '{$subProduct->getCycleType()}'
            ),";
        }
        $query = rtrim($query,',') . ';';

        $this->db->query($query);
    }
}