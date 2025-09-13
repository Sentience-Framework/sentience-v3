<?php

namespace Modules\Models;

use DateTime;
use DateTimeImmutable;
use Modules\Database\Database;
use Modules\Models\Reflection\ReflectionModel;
use Modules\Timestamp\Timestamp;

class Mapper
{
    public static function mapAssoc(string|Model $model, array $assoc): Model
    {
        if (is_string($model)) {
            $model = new $model();
        }

        $reflectionModel = new ReflectionModel($model);

        $reflectionModelProperties = $reflectionModel->getProperties();

        $columns = [];

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            $columns[$reflectionModelProperty->getColumn()] = $reflectionModelProperty;
        }

        foreach ($assoc as $key => $value) {
            if (!array_key_exists($key, $columns)) {
                continue;
            }

            $property = $columns[$key]->getProperty();
            $type = $columns[$key]->getType();

            $model->{$property} = match ($type) {
                'bool' => $dialect->parseBool($value),
                'int' => (int) $value,
                'float' => (float) $value,
                'string' => (string) $value,
                Timestamp::class => $dialect->parseTimestamp($value),
                DateTime::class => $dialect->parseTimestamp($value)->toDateTime(),
                DateTimeImmutable::class => $dialect->parseTimestamp($value)->toDateTimeImmutable(),
                default => $value
            };
        }

        return $model;
    }
}
