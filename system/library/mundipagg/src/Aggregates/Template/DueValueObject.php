<?php

namespace Mundipagg\Aggregates\Template;

use Exception;

class DueValueObject
{
    const TYPE_EXACT = 'E';
    const TYPE_WORKDAY = 'U';

    /** @var string */
    protected $type;
    /** @var int */
    protected $value;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return DueValueObject
     * @throws Exception
     */
    public function setType($type)
    {
        if (!in_array($type, self::getValidTypes())) {
            throw new Exception("Invalid Due Type: $type! ");
        }
        $this->type = $type;
        return $this;
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
     * @return DueValueObject
     * @throws Exception
     */
    public function setValue($value)
    {
        $intValue = intval($value);
        if ($intValue <= 0) {
            throw new Exception("Due value should be greater than 0: $value!");
        }
        $this->value = $intValue;
        return $this;
    }

    public function getDueLabel()
    {
        switch ($this->type) {
            case self::TYPE_EXACT:
                return "Todo dia %d";
            case self::TYPE_WORKDAY:
                return "Todo %d° dia útil";
            default: return "Error: %d : " . $this->type;
        }
    }

    public static function getTypesArray()
    {
        return [
            ['code' => self::TYPE_EXACT,'name' => "Dia exato"],
            ['code' => self::TYPE_WORKDAY,'name' => "Dia útil"]
        ];
    }

    public static function getValidTypes()
    {
        return [
            self::TYPE_EXACT,
            self::TYPE_WORKDAY
        ];
    }
}