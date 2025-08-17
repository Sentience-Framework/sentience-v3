<?php

declare(strict_types=1);

namespace Modules\Models\Traits;

use DateTime;
use DateTimeImmutable;
use Modules\Database\Database;
use Modules\Models\Reflection\ReflectionModel;
use Modules\Timestamp\Timestamp;

trait IsJsonSerializable
{
    public function jsonSerialize(): array
    {
        $dialect = Database::getInstance()->dialect;

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

            $values[$column] = match (get_debug_type($value)) {
                'bool' => $dialect->castBool($value),
                DateTime::class,
                DateTimeImmutable::class => $value->format(Timestamp::JSON),
                default => $value
            };
        }

        return $values;
    }
}
