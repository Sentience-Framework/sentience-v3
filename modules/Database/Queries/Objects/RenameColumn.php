<?php

namespace Modules\Database\Queries\Objects;

class RenameColumn
{
    public function __construct(public string $oldName, public string $newName)
    {
    }
}
