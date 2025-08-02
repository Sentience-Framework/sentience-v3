<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Traits;

use sentience\Database\Queries\Objects\Alias;
use sentience\Database\Queries\Objects\Raw;

trait Table
{
    protected null|string|array|Alias|Raw $table = null;

    public function table(string|array|Alias|Raw $table): static
    {
        $this->table = $table;

        return $this;
    }
}
