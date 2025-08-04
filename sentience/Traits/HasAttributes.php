<?php

declare(strict_types=1);

namespace sentience\Traits;

use ReflectionClass;
use ReflectionProperty;

trait HasAttributes
{
    protected static function getClassAttributes(?string $name = null): array
    {
        return (new ReflectionClass(static::class))->getAttributes($name);
    }

    protected static function getPropertyAttributes(string $property, ?string $name = null): array
    {
        return (new ReflectionProperty(static::class, $property))->getAttributes($name);
    }
}
