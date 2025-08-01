<?php

namespace sentience\Database\queries\traits;

use sentience\Database\queries\objects\Alias;
use sentience\Database\queries\objects\Raw;

trait Table
{
    protected null|string|array|Alias|Raw $table = null;

    public function table(string|array|Alias|Raw $table): static
    {
        $this->table = $table;

        return $this;
    }
}
