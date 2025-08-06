<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Database;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Traits\Models;
use sentience\Models\Model;

abstract class ModelsQueryAbstract extends Query implements ModelsQueryInterface
{
    use Models;

    public function __construct(Database $database, DialectInterface $dialect, string|array|Model $models)
    {
        parent::__construct($database, $dialect);

        $this->models = $this->models = !is_array($models) ? [$models] : $models;
    }
}
