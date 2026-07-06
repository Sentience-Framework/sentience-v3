<?php

namespace Sentience\Ai\Schema;

class StringType extends TypeAbstract
{
    protected ?int $minLength = null;
    protected ?int $maxLength = null;
    protected ?string $pattern = null;

    public function minLength(int $minLength): static
    {
        $this->minLength = $minLength;

        return $this;
    }

    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function pattern(string $pattern): static
    {
        $this->pattern = $pattern;

        return $this;
    }

    public function schema(): array
    {
        $schema = ['type' => $this->nullable ? ['string', 'null'] : 'string'];

        if ($this->minLength !== null) {
            $schema['minLength'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $schema['maxLength'] = $this->maxLength;
        }

        if ($this->pattern !== null) {
            $schema['pattern'] = $this->pattern;
        }

        return $schema;
    }
}
