<?php

namespace Mundipagg\Repositories\Adapters;

use Exception;

abstract class AbstractPlatformDatabaseAdapter
{

    public function __construct()
    {
        $this->db = $this->getDatabaseAccessObject();
        $this->setTableArray();
    }

    public function getTable($tableName)
    {
        if (isset($this->tableArray[$tableName])) {
            return $this->tableArray[$tableName];
        }
        throw new Exception("Table name '$tableName' not found!");
    }

    abstract public function query($query);
    abstract public function getLastId();
    abstract protected function setTableArray();
    abstract protected function getDatabaseAccessObject();
}