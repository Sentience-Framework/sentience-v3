<?php

namespace Sentience\Models\Traits;

use Sentience\Models\Reflection\ReflectionModel;

trait JsonSerializable
{
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
