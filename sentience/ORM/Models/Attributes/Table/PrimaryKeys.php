<?php

namespace Sentience\ORM\Models\Attributes\Table;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PrimaryKeys
{
    public function __construct(public array $columns)
    {
    }
}
