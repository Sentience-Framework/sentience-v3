<?php

namespace Sentience\Ai\StructuredOutput;

class BoolType extends StructuredOutputAbstract
{
    public function schema(): array
    {
        return ['type' => $this->nullable ? ['boolean', 'null'] : 'boolean'];
    }
}
