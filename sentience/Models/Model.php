<?php

namespace Sentience\Models;

use JsonSerializable;
use Sentience\Models\Reflection\ReflectionModel;
use Sentience\Models\Reflection\ReflectionModelProperty;
use Sentience\Traits\HasAttributes;

class Model implements JsonSerializable
{
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

            $values[$column] = $this->{$property};
        }

        return $values;
    }
}
