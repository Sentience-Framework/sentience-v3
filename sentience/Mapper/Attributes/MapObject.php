<?php

namespace Sentience\Mapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MapObject extends MapAbstract
{
    public function __construct(
        string $key,
        public string $class = 'stdClass'
    ) {
        parent::__construct($key);
    }
}
