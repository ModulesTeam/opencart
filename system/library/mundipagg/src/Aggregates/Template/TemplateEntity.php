<?php

namespace Mundipagg\Aggregates\Template;

use Exception;

class TemplateEntity
{
    /** @var int */
    protected $id;
    /** @var boolean */
    protected $isSingle;
    /** @var string */
    protected $name;
    /** @var string */
    protected $description;
    /** @var boolean */
    protected $acceptCreditCard;
    /** @var boolean */
    protected $acceptBoleto;
    /** @var boolean */
    protected $allowInstallments;
    /** @var int */
    protected $trial;
    /** @var string */
    protected $installments;

    public function __construct()
    {
        $this->isSingle =
        $this->acceptCreditCard =
        $this->acceptBoleto =
        $this->allowInstallments =
            false;

        $this->trial =
            0;

        $this->installments = [];
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
     * @return TemplateEntity
     */
    public function setId($id)
    {
        $this->id = intval($id);
        return $this;
    }

    /**
     * @return bool
     */
    public function isSingle()
    {
        return $this->isSingle;
    }

    /**
     * @param bool $isSingle
     * @return TemplateEntity
     */
    public function setIsSingle($isSingle)
    {
        $this->isSingle = boolval(intval($isSingle));
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return TemplateEntity
     * @throws \Exception
     */
    public function setDescription($description)
    {
        if (preg_match('/[^a-zA-Z0-9 ]+/i', $description)) {
            throw new \Exception("The field description must not use special characters.");
        }

        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAcceptCreditCard()
    {
        return $this->acceptCreditCard;
    }

    /**
     * @param bool $acceptCreditCard
     * @return TemplateEntity
     */
    public function setAcceptCreditCard($acceptCreditCard)
    {
        $this->acceptCreditCard = boolval(intval($acceptCreditCard));
        return $this;
    }

    /**
     * @return bool
     */
    public function isAcceptBoleto()
    {
        return $this->acceptBoleto;
    }

    /**
     * @param bool $acceptBoleto
     * @return TemplateEntity
     */
    public function setAcceptBoleto($acceptBoleto)
    {
        $this->acceptBoleto = boolval(intval($acceptBoleto));
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowInstallments()
    {
        return $this->allowInstallments;
    }

    /**
     * @param bool $allowInstallments
     * @return TemplateEntity
     */
    public function setAllowInstallments($allowInstallments)
    {
        $this->allowInstallments = boolval(intval($allowInstallments));
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return TemplateEntity
     * @throws \Exception
     */
    public function setName($name)
    {
        if (preg_match('/[^a-zA-Z0-9 ]+/i', $name)) {
            throw new \Exception("The field name must not use special characters.");
        }

        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getTrial()
    {
        return $this->trial;
    }

    /**
     * @param int $trial
     * @return TemplateEntity
     */
    public function setTrial($trial)
    {
        $this->trial = abs(intval($trial));
        return $this;
    }

    /**
     * @return array
     */
    public function getInstallments()
    {
        return $this->installments;
    }

    /**
     * @param InstallmentValueObject $installment
     * @return TemplateEntity
     * @throws Exception
     */
    public function addInstallment(InstallmentValueObject $installment)
    {
        foreach ($this->installments as $currentInstallment) {
            if ($installment->getValue() == $currentInstallment->getValue()) {
                throw new Exception("This installment is already added: {$installment->getValue()}");
            }
        }
        $this->installments[] = $installment;
        return $this;
    }

    public function addInstallments($installments)
    {
        if (!is_array($installments)) {
            return $this;
        }

        foreach ($installments as $installment) {
            $installmentValueObject = new InstallmentValueObject($installment);
            $this->addInstallment($installmentValueObject);
        }

        return $this;
    }
}