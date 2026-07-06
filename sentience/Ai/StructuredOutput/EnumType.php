<?php

namespace Sentience\Ai\StructuredOutput;

class EnumType extends StructuredOutputAbstract
{
    public function __construct(
        protected array $values,
    ) {
    }

    public function schema(): array
    {
        $type = (function (): string{
            foreach ($this->values as $value) {
                if (is_string($value)) {
                    continue;
                }

                return 'int';
            }

            return 'string';
        })();

        return [
            'type' => $this->nullable ? [$type, 'null'] : $type,
            'enum' => $this->values
        ];
    }
}
