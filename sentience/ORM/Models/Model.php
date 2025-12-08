<?php

namespace Sentience\ORM\Models;

use DateTimeInterface;
use JsonSerializable;
use Sentience\ORM\Models\Reflection\ReflectionModel;

class Model implements JsonSerializable
{
    public static function getTable(): string
    {
        return (new ReflectionModel(static::class))->getTable();
    }

    public static function getColumns(): array
    {
        $reflectionModelProperties = (new ReflectionModel(static::class))->getProperties();

        $columns = [];

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            if (!$reflectionModelProperty->isColumn()) {
                continue;
            }

            $columns[] = $reflectionModelProperty->getColumn();
        }

        return $columns;
    }

    public function jsonSerialize(): array
    {
        $reflectionModel = new ReflectionModel($this);

        $reflectionModelProperties = $reflectionModel->getProperties();

        $values = [];

        foreach ($reflectionModelProperties as $reflectionModelProperty) {
            if (!$reflectionModelProperty->isInitialized($this)) {
                continue;
            }

            $property = $reflectionModelProperty->getProperty();
            $column = $reflectionModelProperty->getColumn();
            $value = $this->{$property};

            $values[$column] = $value;

            $values[$column] = is_subclass_of($value, DateTimeInterface::class)
                ? $value->format('Y-m-d\TH:i:s.v\Z')
                : $value;
        }

        return $values;
    }
}
