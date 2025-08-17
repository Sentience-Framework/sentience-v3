<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Traits;

trait Offset
{
    protected ?int $offset = null;

    public function offset(int $offset): static
    {
        $this->offset = $offset > 0 ? $offset : null;

        return $this;
    }
}
