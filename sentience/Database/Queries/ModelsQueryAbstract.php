<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Traits\Models;
use Sentience\Models\Model;

abstract class ModelsQueryAbstract extends Query implements ModelsQueryInterface
{
    use Models;

    public function __construct(Database $database, DialectInterface $dialect, string|array|Model $models)
    {
        parent::__construct($database, $dialect);

        $this->models = $this->models = !is_array($models) ? [$models] : $models;
    }
}
