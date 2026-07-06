<?php

namespace Sentience\Ai\Schema;

abstract class TypeAbstract implements Schemable
{
    protected bool $nullable = false;
    protected bool $required = false;
    protected ?string $description = null;

    public function nullable(): static
    {
        $this->nullable = true;

        return $this;
    }

    public function required(): static
    {
        $this->required = true;

        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
