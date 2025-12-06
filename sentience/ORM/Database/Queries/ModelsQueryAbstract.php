<?php

namespace Sentience\ORM\Database\Queries;

use BackedEnum;
use DateTime;
use DateTimeImmutable;
use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\QueryException;
use Sentience\ORM\Models\Model;
use Sentience\ORM\Models\Reflection\ReflectionModel;
use Sentience\Timestamp\Timestamp;

abstract class ModelsQueryAbstract implements ModelsQueryInterface
{
    public function __construct(protected Database $database, protected DialectInterface $dialect, protected array $models)
    {
        if (count($models) == 0) {
            throw new QueryException('array of models is empty');
        }
    }

    protected function validateModel(string|object $model, bool $mustBeInstance = true): void
    {
        if (!is_subclass_of($model, Model::class)) {
            throw new QueryException(sprintf('%s is not a model', get_debug_type($model)));
        }

        if (!is_object($model) && $mustBeInstance) {
            throw new QueryException('model is not an instance');
        }
    }

    protected function mapAssocToModel(string|Model $model, array $assoc): Model
    {
        if (is_string($model)) {
            $model = new $model();
        }

        $reflectionModel = new ReflectionModel($model);
        $reflectionModelProperties = $reflectionModel->getProperties();

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            $column = $reflectionModelProperty->getColumn();

            if (!array_key_exists($column, $assoc)) {
                continue;
            }

            $property = $reflectionModelProperty->getProperty();
            $type = $reflectionModelProperty->getType();

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
                DateTime::class => $this->dialect->parseDateTime($value),
                DateTimeImmutable::class => DateTimeImmutable::createFromMutable($this->dialect->parseDateTime($value)),
                Timestamp::class => Timestamp::createFromInterface($this->dialect->parseDateTime($value)),
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
