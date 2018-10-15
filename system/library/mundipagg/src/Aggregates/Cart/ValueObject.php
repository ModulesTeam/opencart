<?php

namespace Mundipagg\Aggregates\Cart;

interface ValueObject
{
    public function equals($object);
}