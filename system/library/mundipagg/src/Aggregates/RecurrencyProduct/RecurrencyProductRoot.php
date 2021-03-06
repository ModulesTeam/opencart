<?php
namespace Mundipagg\Aggregates\RecurrencyProduct;

use Mundipagg\Aggregates\IAggregateRoot;
use Mundipagg\Aggregates\Template\TemplateRoot;

class RecurrencyProductRoot implements IAggregateRoot
{
    /** @var boolean */
    protected $isDisabled;
    /** @var int */
    protected $id;
    /** @var int */
    protected $productId;
    /** @var TemplateRoot */
    protected $template;
    /** @var string */
    protected $mundipaggPlanId;
    /** @var RecurrencySubproductValueObject[] */
    protected $subProducts;
    /** @var boolean */
    protected $isSingle;

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->isDisabled;
    }

    /**
     * @param bool $isDisabled
     * @return RecurrencyProductRoot
     */
    public function setDisabled($isDisabled)
    {
        $this->isDisabled = $isDisabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RecurrencyProductRoot
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @return TemplateRoot
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return string
     */
    public function getMundipaggPlanId()
    {
        return $this->mundipaggPlanId;
    }

    /**
     * @return mixed
     */
    public function getSubProducts()
    {
        return $this->subProducts ? $this->subProducts : [];
    }

    /**
     * @return bool
     */
    public function isSingle()
    {
        return $this->isSingle;
    }

    /**
     * @param int $productId
     * @return RecurrencyProductRoot
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }

    /**
     * @param TemplateRoot $template
     * @return RecurrencyProductRoot
     */
    public function setTemplate(TemplateRoot $template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @param string $mundipaggPlanId
     * @return RecurrencyProductRoot
     */
    public function setMundipaggPlanId($mundipaggPlanId)
    {
        $this->mundipaggPlanId = $mundipaggPlanId;
        return $this;
    }

    public function addSubproduct(RecurrencySubproductValueObject $subProduct)
    {
        $this->subProducts[] = $subProduct;
    }

    /**
     * @param bool $isSingle
     * @return RecurrencyProductRoot
     */
    public function setSingle($isSingle)
    {
        $this->isSingle = $isSingle;
        return $this;
    }

    public function getTemplateId()
    {
        return $this->template->getId();
    }
}