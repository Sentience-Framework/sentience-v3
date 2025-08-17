<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Exceptions\QueryException;
use Modules\Database\Queries\Traits\Models;
use Modules\Helpers\Arrays;
use Modules\Helpers\Reflector;
use Modules\Models\Model;

abstract class ModelsQueryAbstract extends Query implements ModelsQueryInterface
{
    use Models;

    public function __construct(Database $database, DialectInterface $dialect, string|array|Model $models)
    {
        parent::__construct($database, $dialect);

        $this->models = Arrays::wrap($models);
    }

    protected function validateModel(mixed $model, bool $mustBeInstance = true): void
    {
        if (!is_object($model)) {
            if ($mustBeInstance) {
                throw new QueryException('%s is not an instance', get_debug_type($model));
            }

            if (!is_string($model)) {
                throw new QueryException('%s is not a valid type for a model', get_debug_type($model));
            }
        }

        if (!Reflector::isSubclassOf($model, Model::class)) {
            throw new QueryException('%s is not a model', $model::class);
        }

        return;

    }
}
