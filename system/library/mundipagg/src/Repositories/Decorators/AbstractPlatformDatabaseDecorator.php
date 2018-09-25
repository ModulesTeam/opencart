<?php

namespace Mundipagg\Repositories\Decorators;

use Exception;

abstract class AbstractPlatformDatabaseDecorator
{
    protected $db;
    protected $tableArray;

    public function __construct($dbObject)
    {
        $this->db = $dbObject;
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
}