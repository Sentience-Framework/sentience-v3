<?php

declare(strict_types=1);

namespace Sentience\Models\Traits;

use ReflectionClass;
use Sentience\Database\Database;
use Sentience\Timestamp\Timestamp;

trait IsJsonSerializable
{
    public function jsonSerialize(): array
    {
        $dialect = Database::getInstance()->dialect;

        $reflectionClass = new ReflectionClass(static::class);

        $reflectionProperties = $reflectionClass->getProperties();

        $values = [];

        foreach ($reflectionProperties as $reflectionProperty) {
            if (!$reflectionProperty->isInitialized($this)) {
                continue;
            }

            $property = $reflectionProperty->getName();

            $column = static::getColumn($property);

            $value = $this->{$property};

            $values[$column] = match (get_debug_type($value)) {
                'bool' => $dialect->castBool($value),
                Timestamp::class => $dialect->castTimestamp($value),
                default => $value
            };
        }

        return $values;
    }
}
