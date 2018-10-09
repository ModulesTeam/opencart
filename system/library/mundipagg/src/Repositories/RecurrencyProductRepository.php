<?php

namespace Mundipagg\Repositories;

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencySubproductValueObject;
use Mundipagg\Aggregates\IAggregateRoot;
use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Factories\RecurrencyProductRootFactory;

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
        $mundipaggPlanStatus = "NULL";
        if (!empty($recurrencyProduct->getMundipaggPlanStatus())) {
            $mundipaggPlanStatus = "'" . $recurrencyProduct->getMundipaggPlanStatus() . "'";
        }
        $query = "
            INSERT INTO `" . $this->db->getTable('RECURRENCY_PRODUCT_TABLE') . "` (
                `is_disabled`,
                `is_single`,
                `product_id`,
                `template_snapshot`,
                `template_id`,
                `mundipagg_plan_id`,
                `mundipagg_plan_status`,
                `price`
            ) VALUES (
                " . ($recurrencyProduct->isDisabled()?1:0) . ",
                " . ($recurrencyProduct->isSingle()?1:0) . ",
                " . $recurrencyProduct->getProductId() . ",
                '" . json_encode($recurrencyProduct->getTemplate()) . "',
                " . ($templateId ? $templateId : 'NULL')  . ",
                " . ($mundipaggPlanId ? "'$mundipaggPlanId'" : 'NULL') . ",
                " . $mundipaggPlanStatus . ",
                " . $recurrencyProduct->getPrice() . "
            )
        ";

        $this->db->query($query);
        $recurrencyProduct->setid($this->db->getLastId());

        $this->createSubproducts($recurrencyProduct);
    }

    /** @var RecurrencyProductRoot $recurrencyProduct*/
    protected function update(IAggregateRoot &$recurrencyProduct)
    {
        $templateId = intval($recurrencyProduct->getTemplateId());
        $mundipaggPlanId = $recurrencyProduct->getMundipaggPlanId();
        $mundipaggPlanStatus = "NULL";
        if (!empty($recurrencyProduct->getMundipaggPlanStatus())) {
            $mundipaggPlanStatus = "'" . $recurrencyProduct->getMundipaggPlanStatus() . "'";
        }

        $query = "
            UPDATE `" . $this->db->getTable('RECURRENCY_PRODUCT_TABLE') . "` SET
                `is_disabled` = " . ($recurrencyProduct->isDisabled()?1:0) . ",
                `is_single` = " . ($recurrencyProduct->isSingle()?1:0) . ",
                `product_id` = " . (intval($recurrencyProduct->getProductId())) . ",
                `template_snapshot` = '" . (json_encode($recurrencyProduct->getTemplate())) . "',
                `template_id` = ". ($templateId ? $templateId : 'NULL')  .",
                `mundipagg_plan_id` = " . ($mundipaggPlanId ? "'$mundipaggPlanId'" : 'NULL') . ",
                `mundipagg_plan_status` = " . $mundipaggPlanStatus . "
                `price` = " . $recurrencyProduct->getPrice() . "
            WHERE `id` = " . $recurrencyProduct->getId() . "
        ";

        $this->db->query($query);

        $this->deleteSubproducts($recurrencyProduct);
        $this->createSubproducts($recurrencyProduct);
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
        $query = "
            SELECT p.*,
            GROUP_CONCAT(s.product_id) as sub_product_id,
            GROUP_CONCAT(s.cycles) as sub_cycles,
            GROUP_CONCAT(s.cycle_type) as sub_cycle_type,
            GROUP_CONCAT(s.quantity) as sub_quantity,
            GROUP_CONCAT(s.unit_price_in_cents) as sub_unit_price_in_cents
            FROM
            `" . $this->db->getTable('RECURRENCY_PRODUCT_TABLE') . "` as p
            LEFT JOIN `" . $this->db->getTable('RECURRENCY_SUBPRODUCT_TABLE') . "` as s
            ON p.id = s.recurrency_product_id                      
        ";

        if (!$listDisabled) {
            $query .= " WHERE p.is_disabled = false ";
        }

        $query .= " GROUP BY p.id";

        if ($limit !== 0) {
            $limit = intval($limit);
            $query .= " LIMIT $limit";
        }

        $result = $this->db->query($query . ";");

        $recurrencyProductFactory = new RecurrencyProductRootFactory();
        $productRoots = [];

        foreach ($result->rows as $row) {
            $productRoot = $recurrencyProductFactory->createFromDBData($row);
            $productRoots[] = $productRoot;
        }

        return $productRoots;
    }

    /**
     * @param RecurrencyProductRoot $recurrencyProduct
     * @throws \Exception
     */
    protected function createSubproducts(IAggregateRoot &$recurrencyProduct)
    {
        $subProducts = $recurrencyProduct->getSubProducts();

        if (count($subProducts) > 0) {
            $query ="
            INSERT INTO `" . $this->db->getTable('RECURRENCY_SUBPRODUCT_TABLE') . "` (
                    `recurrency_product_id`,
                    `product_id`,
                    `quantity`,
                    `cycles`,
                    `cycle_type`,
                    `unit_price_in_cents`                            
                ) VALUES 
            ";

            /** @var RecurrencySubproductValueObject $subProduct */
            foreach ($subProducts as $subProduct) {
                $query .= "(
                    {$recurrencyProduct->getId()},
                    {$subProduct->getProductId()},
                    {$subProduct->getQuantity()},
                    {$subProduct->getCycles()},
                    '{$subProduct->getCycleType()}',
                    '{$subProduct->getUnitPriceInCents()}'
                ),";
            }
            $query = rtrim($query,',') . ';';

            $this->db->query($query);
        }
    }

    protected function deleteSubproducts($recurrencyProduct)
    {
        $this->db->query("
            DELETE FROM `" . $this->db->getTable('RECURRENCY_SUBPRODUCT_TABLE') . "` WHERE
                `recurrency_product_id` = " . $recurrencyProduct->getId() . "
        ");
    }

    /**
     * @param TemplateRoot $template
     */
    public function removeTemplateDependency(IAggregateRoot $templateRoot)
    {
        $templateId = $templateRoot->getTemplate()->getId();

        $query = "
            UPDATE `" . $this->db->getTable('RECURRENCY_PRODUCT_TABLE') . "` SET
                `template_id` = NULL
                 WHERE `template_id` = {$templateId}
        ";

        $this->db->query($query);
    }

    public function getAllWithTemplateId($templateId, $limit, $listDisabled)
    {
        $query = "
            SELECT p.*,
            GROUP_CONCAT(s.product_id) as sub_product_id,
            GROUP_CONCAT(s.cycles) as sub_cycles,
            GROUP_CONCAT(s.cycle_type) as sub_cycle_type,
            GROUP_CONCAT(s.quantity) as sub_quantity,
            GROUP_CONCAT(s.unit_price_in_cents) as sub_unit_price_in_cents
            FROM
            `" . $this->db->getTable('RECURRENCY_PRODUCT_TABLE') . "` as p
            LEFT JOIN `" . $this->db->getTable('RECURRENCY_SUBPRODUCT_TABLE') . "` as s
            ON p.id = s.recurrency_product_id
        ";

        $query .= "WHERE p.template_id = " . $templateId;

        if (!$listDisabled) {
            $query .= " AND p.is_disabled = false ";
        }

        $query .= " GROUP BY p.id";

        if ($limit !== 0) {
            $limit = intval($limit);
            $query .= " LIMIT $limit";
        }

        $result = $this->db->query($query . ";");

        $recurrencyProductFactory = new RecurrencyProductRootFactory();
        $productRoots = [];

        foreach ($result->rows as $row) {
            $productRoot = $recurrencyProductFactory->createFromDBData($row);
            $productRoots[] = $productRoot;
        }

        return $productRoots;
    }

    public function getByProductId($productId, $listDisabled = false)
    {
        $query = "
            SELECT p.*,
            GROUP_CONCAT(s.product_id) as sub_product_id,
            GROUP_CONCAT(s.cycles) as sub_cycles,
            GROUP_CONCAT(s.cycle_type) as sub_cycle_type,
            GROUP_CONCAT(s.quantity) as sub_quantity,
            GROUP_CONCAT(s.unit_price_in_cents) as sub_unit_price_in_cents
            FROM
            `" . $this->db->getTable('RECURRENCY_PRODUCT_TABLE') . "` as p
            LEFT JOIN `" . $this->db->getTable('RECURRENCY_SUBPRODUCT_TABLE') . "` as s
            ON p.id = s.recurrency_product_id
        ";

        $query .= "WHERE p.product_id = " . $productId;

        if (!$listDisabled) {
            $query .= " AND p.is_disabled = false ";
        }

        $query .= " GROUP BY p.id";

        $query .= " LIMIT 1";

        $result = $this->db->query($query . ";");

        $recurrencyProductFactory = new RecurrencyProductRootFactory();
        $productRoot = null;

        foreach ($result->rows as $row) {
            $productRoot = $recurrencyProductFactory->createFromDBData($row);
            break;
        }

        return $productRoot;
    }
}