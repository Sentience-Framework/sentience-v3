<?php

namespace Sentience\Database\Queries;

use DateTime;
use DateTimeImmutable;
use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Results\ResultsInterface;
use Sentience\Helpers\Arrays;
use Sentience\Helpers\Reflector;
use Sentience\Models\Model;
use Sentience\Models\Reflection\ReflectionModel;
use Sentience\Timestamp\Timestamp;

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
                throw new QueryException(get_debug_type($model) . ' is not an instance');
            }

            if (!is_string($model)) {
                throw new QueryException(get_debug_type($model) . ' is not a valid type for a model');
            }
        }

        if (!Reflector::isSubclassOf($model, Model::class)) {
            throw new QueryException($model::class . ' is not a model');
        }

        return;
    }

    protected function executeQueryWithParams(QueryWithParamsObject $queryWithParams): ResultsInterface
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

            if (is_null($value)) {
                $model->{$property} = null;

                continue;
            }

            $model->{$property} = match ($type) {
                'bool' => $this->dialect->parseBool($value),
                'int' => (int) $value,
                'float' => (float) $value,
                'string' => (string) $value,
                Timestamp::class => $this->dialect->parseTimestamp($value),
                DateTime::class => $this->dialect->parseTimestamp($value)->toDateTime(),
                DateTimeImmutable::class => $this->dialect->parseTimestamp($value)->toDateTimeImmutable(),
                default => $value
            };
        }

        return $model;
    }
}
