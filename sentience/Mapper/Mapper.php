<?php

namespace Sentience\Mapper;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Sentience\Helpers\Arrays;
use Sentience\Mapper\Attributes\MapArray;
use Sentience\Mapper\Attributes\MapObject;
use Sentience\Mapper\Attributes\MapScalar;
use Sentience\Mapper\Exceptions\MapperException;
use stdClass;

class Mapper
{
    public static function toObject(null|array|object $value, string $class = 'stdClass'): null|array|object
    {
        if (is_null($value)) {
            return null;
        }

        if ($class == stdClass::class) {
            return $value;
        }

        return is_array($value)
            ? array_map(
                function (mixed $value) use ($class): mixed {
                    if (is_null($value)) {
                        return $value;
                    }

                    return !is_scalar($value)
                        ? static::mapToClass((object) $value, $class)
                        : $value;
                },
                $value
            )
            : static::mapToClass($value, $class);
    }

    protected static function mapToClass(?object $object, string $class): ?object
    {
        if (is_null($object)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($class);

        $instance = $reflectionClass->newInstance();

        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $property->setAccessible(true);

            $scalarAttributes = $property->getAttributes(MapScalar::class);

            if (!Arrays::empty($scalarAttributes)) {
                $attribute = $scalarAttributes[0]->newInstance();

                $key = $attribute->key;
                $value = $object->{$key};

                if (property_exists($object, $key)) {
                    $property->setValue($instance, $value);
                }

                continue;
            }

            $arrayAttributes = $property->getAttributes(MapArray::class);

            if (!Arrays::empty($arrayAttributes)) {
                $attribute = $arrayAttributes[0]->newInstance();

                $key = $attribute->key;
                $type = $attribute->type;
                $value = $object->{$key};

                if (property_exists($object, $key)) {
                    $property->setValue(
                        $instance,
                        !is_null($value)
                        ? static::mapArray((array) $value, $type)
                        : null
                    );
                }

                continue;
            }

            $objectAttributes = $property->getAttributes(MapObject::class);

            if (!Arrays::empty($objectAttributes)) {
                $attribute = $objectAttributes[0]->newInstance();

                $key = $attribute->key;
                $type = static::getPropertyType($property);
                $value = $object->{$key};

                if (property_exists($object, $key)) {
                    $property->setValue(
                        $instance,
                        !is_null($value)
                        ? static::mapToClass((object) $value, $type)
                        : null
                    );
                }

                continue;
            }
        }

        return $instance;
    }

    protected static function mapArray(array $array, string $type = 'mixed'): array
    {
        return array_map(
            function (mixed $value) use ($type): mixed {
                if (is_null($value)) {
                    return null;
                }

                if ($type == 'mixed') {
                    return $value;
                }

                if (is_array($value) || is_object($value)) {
                    if (static::isNumericArray($value)) {
                        return static::mapArray($value, $type);
                    }

                    if (class_exists($type)) {
                        return static::mapToClass((object) $value, $type);
                    }
                }

                return $value;
            },
            $array
        );
    }

    protected static function getPropertyType(ReflectionProperty $reflectionProperty): ?string
    {
        $reflectionType = $reflectionProperty->getType();

        if (!($reflectionType instanceof ReflectionNamedType)) {
            throw new MapperException('union types are not supported');
        }

        $type = $reflectionType->getName();

        if (!$type) {
            throw new MapperException('mixed types are not supported');
        }

        if (!class_exists($type)) {
            throw new MapperException('target class does not exist');
        }

        return $type;
    }

    protected static function isNumericArray(mixed $array): bool
    {
        if (!is_array($array)) {
            return false;
        }

        foreach ($array as $key => $value) {
            if (!is_numeric($key)) {
                return false;
            }
        }

        return true;
    }
}
