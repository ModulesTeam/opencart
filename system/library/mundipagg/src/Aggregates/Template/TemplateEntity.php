<?php

namespace Mundipagg\Aggregates\Template;

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
    /** @var int */
    protected $trial;

    public function __construct()
    {
        $this->isSingle =
        $this->acceptCreditCard =
        $this->acceptBoleto =
        $this->allowInstallments =
            false;

        $this->trial =
            0;
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
     */
    public function setDescription($description)
    {
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
     */
    public function setName($name)
    {
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
     */
    public function setTrial($trial)
    {
        $this->trial = abs(intval($trial));
        return $this;
    }
}