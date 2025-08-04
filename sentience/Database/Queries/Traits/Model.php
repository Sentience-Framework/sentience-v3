<?php

declare(strict_types=1);

namespace sentience\Database\Queries\Traits;

trait Model
{
    protected null|string|array $model = null;

    public function model(&$class): static
    {
        $this->model = $class;

        return $this;
    }
}
