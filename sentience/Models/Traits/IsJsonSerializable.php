<?php

declare(strict_types=1);

namespace Sentience\Models\Traits;

use DateTime;
use DateTimeImmutable;
use Sentience\Database\Database;
use Sentience\Models\Reflection\ReflectionModel;
use Sentience\Timestamp\Timestamp;

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
