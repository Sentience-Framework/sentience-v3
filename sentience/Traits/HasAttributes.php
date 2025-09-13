<?php

namespace Sentience\Traits;

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
