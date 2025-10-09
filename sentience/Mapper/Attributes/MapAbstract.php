<?php

namespace Sentience\Mapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
abstract class MapAbstract
{
    public function __construct(public string $key)
    {
    }
}
