<?php

namespace Sentience\Ai\Schema;

class IntType extends TypeAbstract
{
    protected ?int $minimum = null;
    protected ?int $maximum = null;

    public function minimum(int $minimum): static
    {
        $this->minimum = $minimum;

        return $this;
    }

    public function maximum(int $maximum): static
    {
        $this->maximum = $maximum;

        return $this;
    }

    public function schema(): array
    {
        $schema = ['type' => $this->nullable ? ['integer', 'null'] : 'integer'];

        if ($this->minimum !== null) {
            $schema['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $schema['maximum'] = $this->maximum;
        }

        return $schema;
    }
}
