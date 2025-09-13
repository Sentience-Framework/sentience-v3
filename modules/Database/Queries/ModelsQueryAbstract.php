<?php

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Exceptions\QueryException;
use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Results\ResultsInterface;
use Modules\Helpers\Arrays;
use Modules\Helpers\Reflector;
use Modules\Models\Model;
use Modules\Models\Reflection\ReflectionModel;

abstract class ModelsQueryAbstract extends Query implements ModelsQueryInterface
{
    public function __construct(Database $database, DialectInterface $dialect, protected array $models)
    {
        parent::__construct($database, $dialect);

        if (Arrays::empty($models)) {
            throw new QueryException('array of models is empty');
        }
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

    protected function executeQueryWithParams(QueryWithParams $queryWithParams): ResultsInterface
    {
        return $this->database->queryWithParams($queryWithParams);
    }

    protected function mapAssocToModel(Model $model, array $assoc): void
    {
        $reflectionModel = new ReflectionModel($model);
        $reflectionModelProperties = $reflectionModel->getProperties();

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            $property = $reflectionModelProperty->getProperty();
            $column = $reflectionModelProperty->getColumn();
            $type = $reflectionModelProperty->getType();

            if (!array_key_exists($column, $assoc)) {
                continue;
            }

            $value = $assoc[$column];

            $model->{$property} = $this->database->parseFromDriver($value, $type);
        }
    }
}
