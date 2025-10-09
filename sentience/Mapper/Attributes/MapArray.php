<?php

namespace Sentience\Mapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MapArray extends MapAbstract
{
    public function __construct(
        string $key,
        public string $type = 'mixed'
    ) {
        parent::__construct($key);
    }
}
