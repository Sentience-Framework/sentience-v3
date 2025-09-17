<?php

namespace Sentience\Database\Queries;

use BackedEnum;
use DateTime;
use DateTimeImmutable;
use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Helpers\Arrays;
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

    protected function validateModel(string|object $model, bool $mustBeInstance = true): void
    {
        if (!is_object($model)) {
            if ($mustBeInstance) {
                throw new QueryException('is not an instance', get_debug_type($model));
            }

            if (!is_string($model)) {
                throw new QueryException('is not a valid type for a model', get_debug_type($model));
            }
        }

        if (!is_subclass_of($model, Model::class)) {
            throw new QueryException('%s is not a model', $model::class);
        }

        return;
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
                default => is_subclass_of($type, BackedEnum::class) ? $type::from($value) : $value
            };
        }

        return $model;
    }

    protected function getValueIfBackedEnum(mixed $value): mixed
    {
        if (is_subclass_of($value, BackedEnum::class)) {
            return $value->value;
        }

        return $value;
    }
}
