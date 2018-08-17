<?php

namespace Mundipagg\Aggregates\RecurrencyProduct;

use Exception;
use Mundipagg\Aggregates\Template\RepetitionValueObject;

class RecurrencySubproductValueObject
{
    /** @var int */
    protected $productId;
    /** @var int */
    protected $quantity;
    /** @var int */
    protected $cycles;
    /** @var string */
    protected $cycleType;

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     * @return RecurrencySubproductValueObject
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return RecurrencySubproductValueObject
     * @throws Exception
     */
    public function setQuantity($quantity)
    {
        $_quantity = intval($quantity);
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than 0! $quantity");
        }
        $this->quantity = $_quantity;
        return $this;
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
     * @return RecurrencySubproductValueObject
     * @throws Exception
     */
    public function setCycles($cycles)
    {
        $_cycles = intval($cycles);
        if ($cycles <= 0) {
            throw new Exception("Quantity must be greater than 0! $cycles");
        }
        $this->cycles = $_cycles;
        return $this;
    }

    /**
     * @return string
     */
    public function getCycleType()
    {
        return $this->cycleType;
    }

    /**
     * @param string $cycleType
     * @return RecurrencySubproductValueObject
     * @throws Exception
     */
    public function setCycleType($cycleType)
    {
        if (!in_array($cycleType, RepetitionValueObject::getValidIntervalTypes())) {
            throw new Exception("Invalid Cycle Type: $cycleType! ");
        }

        $this->cycleType = $cycleType;
        return $this;
    }
}