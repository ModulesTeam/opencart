<?php

namespace Mundipagg\Aggregates\Template;

use Mundipagg\Aggregates\IAggregateRoot;

class TemplateRoot implements IAggregateRoot
{
    /** @var bool */
    protected $isDisabled;
    /** @var TemplateEntity */
    protected $template;
    /** @var DueValueObject */
    protected $dueAt;
    /** @var RepetitionValueObject[] */
    protected $repetitions;

    public function __construct()
    {
        $this->isDisabled = false;
    }

    /**
     * @return TemplateEntity
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param TemplateEntity $template
     * @return TemplateRoot
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return DueValueObject
     */
    public function getDueAt()
    {
        return $this->dueAt;
    }

    /**
     * @param DueValueObject $dueAt
     * @return TemplateRoot
     */
    public function setDueAt($dueAt)
    {
        $this->dueAt = $dueAt;
        return $this;
    }

    /**
     * @return array
     */
    public function getRepetitions()
    {
        return $this->repetitions;
    }

    /**
     * @param RepetitionValueObject $repetitions
     * @return TemplateRoot
     */
    public function addRepetition($repetition)
    {
        $this->repetitions[] = $repetition;
        return $this;
    }

    public function getId()
    {
        return $this->template->getId();
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->isDisabled;
    }

    /**
     * @param bool $isDisabled
     */
    public function setDisabled($isDisabled)
    {
        $this->isDisabled = boolval($isDisabled);
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
        $repetitions = [];
        foreach ($this->repetitions as $repetition) {
            $repetitions[] = [
                "cycles" => $repetition->getCycles(),
                "discountType" => $repetition->getDiscountType(),
                "discountValue" => $repetition->getDiscountValue(),
                "frequency" => $repetition->getFrequency(),
                "intervalType" => $repetition->getIntervalType()
            ];
        }
        return [
            "id" => $this->template->getId(),
            "isDisabled" => $this->isDisabled,
            "template" => [
                "acceptBoleto" => $this->template->isAcceptBoleto(),
                "acceptCreditCard" => $this->template->isAcceptCreditCard(),
                "allowInstallments" => $this->template->isAllowInstallments(),
                "description" => $this->template->getDescription(),
                "id" => $this->template->getId(),
                "isSingle" => $this->template->isSingle(),
                "name" => $this->template->getName(),
                "trial" => $this->template->getTrial()
            ],
            "dueAt" => [
                "type" => $this->dueAt->getType(),
                "value" => $this->dueAt->getValue()
            ],
            "repetitions" => $repetitions,
        ];
    }
}