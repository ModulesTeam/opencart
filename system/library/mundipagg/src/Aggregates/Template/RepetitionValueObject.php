<?php

namespace Mundipagg\Aggregates\Template;

use Exception;

class RepetitionValueObject
{
    const DISCOUNT_TYPE_FIXED = 'F';
    const DISCOUNT_TYPE_PERCENT = 'P';

    const INTERVAL_TYPE_WEEKLY = 'W';
    const INTERVAL_TYPE_MONTHLY = 'M';
    const INTERVAL_TYPE_YEARLY = 'Y';

    /** @var int */
    protected $frequency;
    /** @var string */
    protected $intervalType;
    /** @var string */
    protected $discountType;
    /** @var float */
    protected $discountValue;
    /** @var int */
    protected $cycles;

    public function __construct()
    {
        $this->cycles = 0;
    }

    /**
     * @return int
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * @param int $frequency
     * @return RepetitionValueObject
     * @throws Exception
     */
    public function setFrequency($frequency)
    {
        $intValue = intval($frequency);
        if ($intValue <= 0) {
            throw new Exception(
                "Interval frequency should be greater than 0: $frequency!"
            );
        }
        $this->frequency = $intValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getIntervalType()
    {
        return $this->intervalType;
    }

    /**
     * @param string $intervalType
     * @return RepetitionValueObject
     * @throws Exception
     */
    public function setIntervalType($intervalType)
    {
        if (!in_array($intervalType, self::getValidIntervalTypes())) {
            throw new Exception("Invalid Interval Type: $intervalType! ");
        }

        $this->intervalType = $intervalType;
        return $this;
    }

    /**
     * @return string
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * @param string $discountType
     * @return RepetitionValueObject
     * @throws Exception
     */
    public function setDiscountType($discountType)
    {
        if (!in_array($discountType, self::getValidDiscountTypes())) {
            throw new Exception("Invalid Interval Discount Type: $discountType! ");
        }

        $this->discountType = $discountType;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountValue()
    {
        return $this->discountValue;
    }

    /**
     * @param float $discountValue
     * @return RepetitionValueObject
     */
    public function setDiscountValue($discountValue)
    {
        $this->discountValue = abs(floatval($discountValue));
        return $this;
    }

    public function getDiscountValueLabel()
    {
        switch ($this->discountType) {
            case self::DISCOUNT_TYPE_FIXED:
                return "%s%.2f";
            case self::DISCOUNT_TYPE_PERCENT:
                return"%s%.2f%%";
            default:
                return "Error: %s : %.2f";
        }
    }

    public function getIntervalTypeLabel()
    {
        switch ($this->intervalType) {
            case self::INTERVAL_TYPE_WEEKLY:
                return $this->frequency > 1 ? "weeks" : "week";
            case self::INTERVAL_TYPE_MONTHLY:
                return $this->frequency > 1 ? "months" : "month";
            case self::INTERVAL_TYPE_YEARLY:
                return $this->frequency > 1 ? "years" : "year";
        }
    }

    public static function getDiscountTypesArray()
    {
        return [
            ['code'=>self::DISCOUNT_TYPE_PERCENT, 'name' => '%'],
            ['code'=>self::DISCOUNT_TYPE_FIXED, 'name' => "R$"]
        ];
    }

    public static function getIntervalTypesArray()
    {
        return [
            ['code'=>self::INTERVAL_TYPE_WEEKLY, 'name'=> "Semanal"],
            ['code'=>self::INTERVAL_TYPE_MONTHLY, 'name'=> "Mensal"],
            ['code'=>self::INTERVAL_TYPE_YEARLY, 'name'=> "Anual"]
        ];
    }

    public static function getValidIntervalTypes()
    {
        return [
            self::INTERVAL_TYPE_WEEKLY,
            self::INTERVAL_TYPE_MONTHLY,
            self::INTERVAL_TYPE_YEARLY
        ];
    }

    public static function getValidDiscountTypes()
    {
        return [
            self::DISCOUNT_TYPE_PERCENT,
            self::DISCOUNT_TYPE_FIXED
        ];
    }

    /**
     * @return int
     */
    public function getCycles()
    {
        return $this->cycles;
    }

    /**
     * @param int $cycles
     */
    public function setCycles($cycles)
    {
        $this->cycles = abs(intval($cycles));
        return $this;
    }
}