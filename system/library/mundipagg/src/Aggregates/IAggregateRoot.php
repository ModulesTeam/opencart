<?php

namespace Mundipagg\Aggregates;

use JsonSerializable;

interface IAggregateRoot extends JsonSerializable
{
    public function isDisabled();
    public function setDisabled($disabled);
    public function getId();
}