<?php

declare(strict_types=1);

namespace Modules\Models\Attributes\Table;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UniqueConstraint
{
    public function __construct(
        public array $columns,
        public ?string $name = null
    ) {
    }
}
