<?php

declare(strict_types=1);

namespace Sentience\Database\Queries\Objects;

class RenameColumn
{
    public function __construct(public string $oldName, public string $newName)
    {
    }
}
