<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

trait IfNotExists
{
    protected bool $ifNotExists = false;

    public function ifNotExists(): static
    {
        $this->ifNotExists = true;

        return $this;
    }
}
