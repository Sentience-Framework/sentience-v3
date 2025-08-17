<?php

declare(strict_types=1);

namespace Sentience\Models\Attributes\Table;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PrimaryKeys
{
    public function __construct(public array $columns)
    {
    }
}
