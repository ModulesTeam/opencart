<?php

namespace Mundipagg\Repositories\Decorators;

use DB;

class OpencartPlatformDatabaseDecorator extends AbstractPlatformDatabaseDecorator
{
    protected function setTableArray()
    {
        $this->tableArray = [
            "TEMPLATE_TABLE" =>  DB_PREFIX . "mundipagg_template",
            "TEMPLATE_REPETITION_TABLE" =>  DB_PREFIX . "mundipagg_template_repetition",
            "RECURRENCY_PRODUCT_TABLE" => DB_PREFIX . "mundipagg_recurrency_product",
            "RECURRENCY_SUBPRODUCT_TABLE" => DB_PREFIX . "mundipagg_recurrency_subproduct",
        ];
    }

    public function query($query)
    {
        return $this->db->query($query);
    }

    public function getLastId()
    {
        return $this->db->getLastId();
    }
}