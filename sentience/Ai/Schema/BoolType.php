<?php

namespace Sentience\Ai\Schema;

class BoolType extends TypeAbstract
{
    public function schema(): array
    {
        return ['type' => $this->nullable ? ['boolean', 'null'] : 'boolean'];
    }
}
