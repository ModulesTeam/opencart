<?php

namespace Mundipagg\Repositories;

use Mundipagg\Aggregates\IAggregateRoot;
use Mundipagg\Repositories\Adapters\AbstractPlatformDatabaseDecorator;

abstract class AbstractRep
{
    /** @var AbstractPlatformDatabaseDecorator */
    protected $db;

    /**
     * AbstractRep constructor.
     * @param AbstractPlatformDatabaseDecorator $db
     */
    public function __construct(AbstractPlatformDatabaseDecorator $db)
    {
        $this->db = $db;
    }

    public function save(IAggregateRoot &$object){
        $objectId = null;
        if (
            is_object($object) &&
            method_exists($object, 'getId')
        ) {
            $objectId = $object->getId();
        }
        if ($objectId === null) {
            return $this->create($object);
        }

        return $this->update($object);
    }

    abstract protected function create(IAggregateRoot &$object);
    abstract protected function update(IAggregateRoot &$object);
    abstract public function delete(IAggregateRoot $object);
    abstract public function find($objectId);
    abstract public function listEntities($limit, $listDisabled);
}