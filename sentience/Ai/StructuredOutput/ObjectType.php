<?php

namespace Sentience\Ai\StructuredOutput;

class ObjectType extends StructuredOutputAbstract
{
    public function __construct(protected array $properties)
    {
    }

    public function schema(): array
    {
        $schema = [
            'type' => $this->nullable ? ['object', 'null'] : 'object',
            'properties' => array_map(
                fn(StructuredOutputInterface $structuredOutputInterface): array => $structuredOutputInterface->schema(),
                $this->properties
            ),
            'required' => array_keys($this->properties)
        ];

        return $schema;
    }
}
