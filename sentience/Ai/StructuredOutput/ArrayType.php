<?php

namespace Sentience\Ai\StructuredOutput;

class ArrayType extends StructuredOutputAbstract
{
    public function __construct(protected StructuredOutputInterface|array $items)
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
