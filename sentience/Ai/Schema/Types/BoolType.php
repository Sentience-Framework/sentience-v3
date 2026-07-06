<?php

namespace Sentience\Ai\Schema\Types;

abstract class BoolType extends TypeAbstract
{
    public const SCHEMA_TYPE = 'boolean';

    public function schema(): array
    {
        return ['type' => $this->nullable ? ['boolean', 'null'] : 'boolean'];
    }
}
