<?php

namespace Sentience\Ai\Schema;

class ArrayType extends TypeAbstract
{
    public function __construct(protected Schemable|array $items)
    {
    }

    public function schema(): array
    {
        $items = function ($item) use (&$items): mixed {
            if ($item instanceof StructuredOutputInterface) {
                return $item->schema();
            }

            if (is_array($item)) {
                return array_map(
                    fn($item) => $items($item),
                    $item
                );
            }

            return $item;
        };

        return [
            'type' => $this->nullable ? ['array', 'null'] : 'array',
            'items' => $items($this->items),
        ];
    }
}
