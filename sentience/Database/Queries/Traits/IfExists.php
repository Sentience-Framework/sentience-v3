<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Traits;

trait IfExists
{
    protected bool $ifExists = false;

    public function ifExists(): static
    {
        $this->ifExists = true;

        return $this;
    }
}
