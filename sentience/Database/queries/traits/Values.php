<?php

namespace sentience\Database\queries\traits;

trait Values
{
    protected array $values = [];

    public function values(array $values): static
    {
        $this->values = $values;

        return $this;
    }
}
