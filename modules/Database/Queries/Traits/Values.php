<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Traits;

trait Values
{
    protected array $values = [];

    public function values(array $values): static
    {
        $this->values = $values;

        return $this;
    }
}
