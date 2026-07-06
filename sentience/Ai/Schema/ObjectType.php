<?php

namespace Sentience\Ai\Schema;

class ObjectType extends TypeAbstract
{
    public function __construct(protected array $properties)
    {
    }

    public function schema(): array
    {
        $schema = [
            'type' => $this->nullable ? ['object', 'null'] : 'object',
            'properties' => array_map(
                fn(Schemable $type): array => $type->schema(),
                $this->properties
            ),
            'required' => array_keys($this->properties)
        ];

        return $schema;
    }
}
