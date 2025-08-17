<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Traits;

trait Returning
{
    protected ?array $returning = null;

    public function returning(array $columns = []): static
    {
        $this->returning = $columns;

        return $this;
    }
}
