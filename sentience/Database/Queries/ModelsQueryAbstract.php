<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultsInterface;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Reflector;
use Sentience\Models\Model;
use Sentience\Models\Reflection\ReflectionModel;

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

    protected function mapAssocToModel(string|Model $model, array $assoc): Model
    {
        if (is_string($model)) {
            $model = new $model();
        }

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

        return $model;
    }
}
