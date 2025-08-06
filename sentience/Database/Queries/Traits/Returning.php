<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

trait Returning
{
    protected ?array $returning = null;

    public function returning(array $columns = []): static
    {
        $this->returning = $columns;

        return $this;
    }
}
