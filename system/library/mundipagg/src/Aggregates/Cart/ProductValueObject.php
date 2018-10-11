<?php

namespace Mundipagg\Aggregates\Cart;


use phpDocumentor\Reflection\Types\Boolean;

class ProductValueObject implements ValueObject
{
    const NORMAL = 'N';
    const PLAN = 'P';
    const SINGLE = 'S';

    private $type;
    private $templateId;
    private $mixed;

    protected function __construct($type, $templateId, $mixed)
    {
        $this->setType($type);
        $this->setTemplateId($templateId);
        $this->setMixed($mixed);
    }

    protected function setType($type)
    {
        $this->type = $type;
    }

    protected function setTemplateId(int $templateId)
    {
        $this->templateId = $templateId;
    }

    protected function setMixed(bool $mixed)
    {
       $this->mixed =  $mixed;
    }

    public function getTemplateId()
    {
        return $this->templateId;
    }

    public function getMixed()
    {
        return $this->mixed;
    }

    public function getType()
    {
        return $this->type;
    }

    public static function normal()
    {
        return new ProductValueObject(self::NORMAL,0, false);
    }

    public static function plan()
    {
        return new ProductValueObject(self::PLAN,0, false);
    }

    public static function single($templateId = 0, $mixed = false)
    {
        return new ProductValueObject(self::SINGLE, $templateId, $mixed);
    }

    /**
     * @param ProductValueObject $object
     * @return bool
     */
    public function equals($object)
    {
        return
            $this->type === $object->getType() &&
            $this->mixed === $object->getMixed() &&
            $this->templateId === $object->getTemplateId()
            ;
    }
}