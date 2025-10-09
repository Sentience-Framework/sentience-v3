<?php

namespace Sentience\Mapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MapScalar extends MapAbstract
{
    public function __construct(
        string $key,
    ) {
        parent::__construct($key);
    }
}
