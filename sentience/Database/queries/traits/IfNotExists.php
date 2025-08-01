<?php

namespace sentience\Database\queries\traits;

trait IfNotExists
{
    protected bool $ifNotExists = false;

    public function ifNotExists(): static
    {
        $this->ifNotExists = true;

        return $this;
    }
}
