<?php

namespace Mundipagg\Aggregates\Template;

use Exception;

class PlanStatusValueObject
{
    const STATUS_ACTIVE = "active";
    const STATUS_INACTIVE = "inactive";
    const STATUS_DELETED = "deleted";

    /** @var string */
    protected $value;

    public function __construct($value)
    {
        $this->setValue($value);
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @throws Exception
     */
    private function setValue($value)
    {
        $newStatus = trim(strtolower($value));
        $validValues = [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_DELETED,
        ];
        if (!in_array($newStatus, $validValues)) {
            throw new Exception("'$value' is not a valid value!");
        }

        $this->value = $newStatus;
    }
}