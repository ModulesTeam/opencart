<?php

namespace Mundipagg\Aggregates\Template;

use JsonSerializable;
use Unirest\Exception;

class InstallmentValueObject implements JsonSerializable
{
    /**
     * @var int
     */
    protected $value;

    public function __construct($value)
    {
        $this->setValue($value);
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     * @return InstallmentValueObject
     * @throws Exception
     */
    protected function setValue($value)
    {
        $newValue = intval($value);
        if ($newValue < 1 || $newValue > 12) {
            throw new Exception("The installments field must be between 1 and 12: $value");
        }

        $this->value = $newValue;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
}