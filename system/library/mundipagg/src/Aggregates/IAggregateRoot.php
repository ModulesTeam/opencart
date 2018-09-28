<?php

namespace Mundipagg\Aggregates;

use JsonSerializable;

/**
 * Interface IAggregateRoot
 *
 * @todo All validation exceptions throwed by Entities or Value Objects MUST be
 * @todo of a specific type, to handle and inform validation erros to UI.
 *
 * @package Mundipagg\Aggregates
 */
interface IAggregateRoot extends JsonSerializable
{
    public function isDisabled();
    public function setDisabled($disabled);
    public function getId();
}