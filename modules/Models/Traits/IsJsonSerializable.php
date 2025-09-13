<?php

namespace Modules\Models\Traits;

use Modules\Models\Reflection\ReflectionModel;

trait IsJsonSerializable
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
