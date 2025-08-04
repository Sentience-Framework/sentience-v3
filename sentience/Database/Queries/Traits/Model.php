<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Traits;

trait Model
{
    protected ?string $model = null;

    public function model(string $class): static
    {
        $this->model = $class;

        return $this;
    }
}
