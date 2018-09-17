<?php

namespace Mundipagg\Repositories;

use Mundipagg\Aggregates\IAggregateRoot;
use Mundipagg\Aggregates\Template\RepetitionValueObject;
use Mundipagg\Aggregates\Template\TemplateRoot;
use Mundipagg\Factories\TemplateRootFactory;

class TemplateRepository extends AbstractRep
{
    /**
     * @param TemplateRoot $templateRoot
     */
    protected function create(IAggregateRoot &$templateRoot)
    {
        $query = "
            INSERT INTO `" . $this->db->getTable('TEMPLATE_TABLE') . "` (
                `is_disabled`,
                `is_single`,
                `name`,
                `description`,
                `accept_credit_card`,
                `accept_boleto`,
                `allow_installments`,
                `trial`,
                `due_type`,
                `due_value`,
                `installments`
            ) VALUES (
                " . ($templateRoot->isDisabled()?1:0) . ",
                " . ($templateRoot->getTemplate()->isSingle()?1:0) . ",
                '" . $templateRoot->getTemplate()->getName() . "',
                '" . $templateRoot->getTemplate()->getDescription() . "',
                " . ($templateRoot->getTemplate()->isAcceptCreditCard()?1:0) . ",
                " . ($templateRoot->getTemplate()->isAcceptBoleto()?1:0) . ",
                " . ($templateRoot->getTemplate()->isAllowInstallments()?1:0) . ",                
                " . $templateRoot->getTemplate()->getTrial() . ",
                '" . $templateRoot->getDueAt()->getType() . "',
                " . $templateRoot->getDueAt()->getValue() . ",
                '" . json_encode($templateRoot->getTemplate()->getInstallments()) . "'
            )
        ";

        $this->db->query($query);
        $templateRoot->getTemplate()->setId($this->db->getLastId());

        $this->createTemplateRepetitions($templateRoot);

        return true;
    }

    /**
     * @param TemplateRoot $templateRoot
     */
    protected function update(IAggregateRoot &$templateRoot)
    {
        $query = "
            UPDATE `" . $this->db->getTable('TEMPLATE_TABLE') . "` SET
                `is_disabled` = " . ($templateRoot->isDisabled()?1:0) . ",
                `is_single` = " . ($templateRoot->getTemplate()->isSingle()?1:0) . ",
                `name` = '" . $templateRoot->getTemplate()->getName() . "',
                `description` = '" . $templateRoot->getTemplate()->getDescription() . "',
                `accept_credit_card` = " . ($templateRoot->getTemplate()->isAcceptCreditCard()?1:0) . ",
                `accept_boleto` = " . ($templateRoot->getTemplate()->isAcceptBoleto()?1:0) . ",
                `allow_installments` = " . ($templateRoot->getTemplate()->isAllowInstallments()?1:0) . ", 
                `trial` = " . $templateRoot->getTemplate()->getTrial() . ",
                `due_type` = '" . $templateRoot->getDueAt()->getType() . "',
                `due_value` = " . $templateRoot->getDueAt()->getValue() . ",
                `installments` = '" . json_encode($templateRoot->getTemplate()->getInstallments()) . "'
            WHERE `id` = " . $templateRoot->getId() . "
        ";

        $this->db->query($query);

        $this->deleteTemplateRepetitions($templateRoot);
        $this->createTemplateRepetitions($templateRoot);
    }

    public function delete(IAggregateRoot $templateRoot)
    {
        $query = "
            UPDATE `" . $this->db->getTable('TEMPLATE_TABLE') . "` SET
                `is_disabled` = true
             WHERE `id` = " . $templateRoot->getId() . "                         
        ";
        $this->db->query($query);

        return true;
    }

    public function find($templateId)
    {
        $query = "
             SELECT 
              t.*,
              GROUP_CONCAT(r.frequency) AS frequency, 
              GROUP_CONCAT(r.interval_type) AS interval_type,
              GROUP_CONCAT(r.discount_type) AS discount_type, 
              GROUP_CONCAT(r.discount_value) AS discount_value,      
              GROUP_CONCAT(r.cycles) AS cycles      
            FROM `" . $this->db->getTable('TEMPLATE_TABLE') . "` AS t 
            INNER JOIN `" . $this->db->getTable('TEMPLATE_REPETITION_TABLE') . "` AS r
              ON t.id = r.template_id
            WHERE t.id = " . intval($templateId) . "  
            GROUP BY t.id  
        ";

        $result = $this->db->query($query . ";");
        if ($result->num_rows < 1 ) {
           return null;
        }

        return (new TemplateRootFactory())
            ->createFromDBData($result->rows[0]);
    }

    public function listEntities($limit = 0, $listDisabled = true)
    {
        $query = "
            SELECT 
              t.*,
              GROUP_CONCAT(r.frequency) AS frequency, 
              GROUP_CONCAT(r.interval_type) AS interval_type,
              GROUP_CONCAT(r.discount_type) AS discount_type, 
              GROUP_CONCAT(r.discount_value) AS discount_value,      
              GROUP_CONCAT(r.cycles) AS cycles      
            FROM `" . $this->db->getTable('TEMPLATE_TABLE') . "` AS t 
            INNER JOIN `" . $this->db->getTable('TEMPLATE_REPETITION_TABLE') . "` AS r
              ON t.id = r.template_id             
        ";

        if (!$listDisabled) {
            $query .= " WHERE t.is_disabled = false ";
        }

        $query .= " GROUP BY t.id";

        if ($limit !== 0) {
            $limit = intval($limit);
            $query .= " LIMIT $limit";
        }

        $result = $this->db->query($query . ";");

        $templateRootFactory = new TemplateRootFactory();
        $templateRoots = [];

        foreach ($result->rows as $row) {
            $templateRoot = $templateRootFactory->createFromDBData($row);
            $templateRoots[] = $templateRoot;
        }

        return $templateRoots;
    }

    protected function createTemplateRepetitions($templateRoot)
    {
        $query = "
            INSERT INTO `" . $this->db->getTable('TEMPLATE_REPETITION_TABLE') . "` (
                `template_id`,
                `cycles`,
                `frequency`,
                `interval_type`,
                `discount_type`,
                `discount_value`
            ) VALUES 
        ";

        /** @var RepetitionValueObject $repetition */
        foreach ($templateRoot->getRepetitions() as $repetition) {
            $query .= "(
                ". $templateRoot->getTemplate()->getId() .",
                ". $repetition->getCycles() . ",
                ". intval($repetition->getFrequency()) .",
                '". $repetition->getIntervalType() ."',
                '". $repetition->getDiscountType() ."',
                ". floatval($repetition->getDiscountValue()) ."
            ),";
        }
        $query = rtrim($query,',') . ';';

        $this->db->query($query);
    }

    protected function deleteTemplateRepetitions($templateRoot)
    {
        $this->db->query("
            DELETE FROM `" . $this->db->getTable('TEMPLATE_REPETITION_TABLE') . "` WHERE
                `template_id` = " . $templateRoot->getTemplate()->getId() . "
        ");
    }
}