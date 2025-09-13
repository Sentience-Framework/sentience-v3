<?php

namespace Modules\Database\Queries\Traits;

trait IfNotExists
{
    protected bool $ifNotExists = false;

    public function ifNotExists(): static
    {
        $this->ifNotExists = true;

        return $this;
    }
}
