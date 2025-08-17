<?php

declare(strict_types=1);

namespace Sentience\Models;

use DateTime;
use DateTimeImmutable;
use Sentience\Database\Database;
use Sentience\Models\Reflection\ReflectionModel;
use Sentience\Models\Reflection\ReflectionModelProperty;
use Sentience\Timestamp\Timestamp;
use Sentience\Traits\HasAttributes;

class Model
{
    use HasAttributes;

    public function fromDatabase(array $assoc): static
    {
        $reflectionModel = new ReflectionModel($this);

        $reflectionModelProperties = $reflectionModel->getProperties();

        $columns = [];

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            $columns[$reflectionModelProperty->getColumn()] = $reflectionModelProperty;
        }

        $dialect = Database::getInstance()->dialect;

        foreach ($assoc as $key => $value) {
            if (!array_key_exists($key, $columns)) {
                continue;
            }

            $property = $columns[$key]->getProperty();
            $type = $columns[$key]->getType();

            $this->{$property} = match ($type) {
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

        return $this;
    }

    public static function getTable(): string
    {
        return (new ReflectionModel(static::class))->getTable();
    }

    public static function getColumns(): array
    {
        return array_map(
            fn(ReflectionModelProperty $reflectionModelProperty): string => $reflectionModelProperty->getColumn(),
            (new ReflectionModel(static::class))->getProperties()
        );
    }
}
