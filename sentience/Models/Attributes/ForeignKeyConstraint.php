<?php

declare(strict_types=1);

namespace sentience\Models\Attributes;

use Attribute;

#[Attribute]
class ForeignKeyConstraint
{
    public function __construct(
        public string $model,
        public string $referenceProperty
    ) {
    }
}
