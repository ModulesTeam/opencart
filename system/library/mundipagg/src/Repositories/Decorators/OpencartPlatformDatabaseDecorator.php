<?php

namespace Mundipagg\Repositories\Decorators;

use DB;

class OpencartPlatformDatabaseDecorator extends AbstractPlatformDatabaseDecorator
{
    protected $db;
    protected $tableArray;

    protected function setTableArray()
    {
        $this->tableArray = [
            "TEMPLATE_TABLE" =>  DB_PREFIX . "mundipagg_template",
            "TEMPLATE_REPETITION_TABLE" =>  DB_PREFIX . "mundipagg_template_repetition",
            "RECURRENCY_PRODUCT_TABLE" => DB_PREFIX . "mundipagg_recurrency_product",
            "RECURRENCY_SUBPRODUCT_TABLE" => DB_PREFIX . "mundipagg_recurrency_subproduct",
        ];
    }

    protected function getDatabaseAccessObject()
    {
        return new DB(
            DB_DRIVER,
            DB_HOSTNAME,
            DB_USERNAME,
            DB_PASSWORD,
            DB_DATABASE);
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