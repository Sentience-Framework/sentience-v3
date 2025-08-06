<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Objects;

class UniqueConstraint
{
    public function __construct(public array $columns, public ?string $name = null)
    {
    }
}
