<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Traits\Models;
use Sentience\Exceptions\QueryException;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Reflector;
use Sentience\Models\Model;

abstract class ModelsQueryAbstract extends Query implements ModelsQueryInterface
{
    use Models;

    public function __construct(Database $database, DialectInterface $dialect, string|array|Model $models)
    {
        parent::__construct($database, $dialect);

        $this->models = Arrays::wrap($models);
    }

    protected function validateModel(mixed $model): void
    {
        if (!is_string($model) && !is_object($model)) {
            throw new QueryException('%s is not a valid type for a model', get_debug_type($model));
        }

        if (!Reflector::isSubclassOf($model, Model::class)) {
            throw new QueryException('%s is not a model', $model::class);
        }
    }
}
