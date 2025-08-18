<?php

declare(strict_types=1);

namespace Modules\Database\Queries\Traits;

trait Relations
{
    protected array $relations = [];

    public function relation(string $relation): static
    {
        $this->relation[] = $relation;

        return $this;
    }
}
