<?php

namespace Sentience\ORM\Database\Queries\Traits;

use Closure;

trait RelationsTrait
{
    protected array $relations = [];

    public function relations(array $relations): static
    {
        foreach ($relations as $relation => $callback) {
            is_int($relation)
                ? $this->relation($callback)
                : $this->relation($relation, $callback);
        }

        return $this;
    }

    public function relation(string $relation, ?callable $callback = null): static
    {
        $this->relations[$relation] = $callback ? Closure::fromCallable($callback) : null;

        return $this;
    }
}
