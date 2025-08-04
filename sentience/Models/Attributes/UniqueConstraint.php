<?php

declare(strict_types=1);

namespace sentience\Models\Attributes;

class UniqueConstraint
{
    public function __construct(
        public array $properties,
        public ?string $name = null
    ) {
    }
}
