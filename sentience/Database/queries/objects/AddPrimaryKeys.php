<?php

namespace sentience\Database\queries\objects;

class AddPrimaryKeys
{
    public array $columns;

    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }
}
