<?php

namespace Sentience\ORM\Database\Queries\Traits;

trait RelationsTrait
{
    protected array $relations = [];

    public function relation(string $relation): static
    {
        $this->relations[] = $relation;

        return $this;
    }
}
