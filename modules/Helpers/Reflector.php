<?php

declare(strict_types=1);

namespace Modules\Helpers;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Stringable;

class Reflector
{
    public static function getShortName(string|object $objectOrClass): string
    {
        $reflectionClass = new ReflectionClass($objectOrClass);

        return $reflectionClass->getShortName();
    }

    public static function isPropertyInitialized(object $class, string $property): bool
    {
        $reflectionClass = new ReflectionProperty($class, $property);

        return $reflectionClass->isInitialized($class);
    }

    public static function hasDefaultValue(string|object $objectOrClass, string $property): bool
    {
        $reflectionProperty = new ReflectionProperty($objectOrClass, $property);

        return $reflectionProperty->hasDefaultValue();
    }

    public static function getDefaultValue(string|object $objectOrClass, string $property): mixed
    {
        $reflectionProperty = new ReflectionProperty($objectOrClass, $property);

        if (!$reflectionProperty->hasDefaultValue()) {
            return null;
        }

        return $reflectionProperty->getDefaultValue();
    }

    public static function isSubclassOf(string|object $objectOrClass, string $parent): mixed
    {
        $reflectionClass = new ReflectionClass($objectOrClass);

        return $reflectionClass->isSubclassOf($parent);
    }

    public static function hasNamedType(string|object $objectOrClass, string $property): bool
    {
        $reflectionProperty = new ReflectionProperty($objectOrClass, $property);

        $reflectionType = $reflectionProperty->getType();

        if (is_null($reflectionType)) {
            return false;
        }

        if (!($reflectionType instanceof ReflectionNamedType)) {
            return false;
        }

        $propertyType = $reflectionType->getName();

        if ($propertyType == 'mixed') {
            return false;
        }

        return true;
    }

    public static function classHasAttribute(string|object $objectOrClass, string $attribute): bool
    {
        $reflectionClass = new ReflectionClass($objectOrClass);

        return !Arrays::empty($reflectionClass->getAttributes($attribute));
    }

    public static function propertyHasAttribute(string|object $objectOrClass, string $property, string $attribute): bool
    {
        $reflectionProperty = new ReflectionProperty($objectOrClass, $property);

        return !Arrays::empty($reflectionProperty->getAttributes($attribute));
    }
}
