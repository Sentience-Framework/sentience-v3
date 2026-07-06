<?php

namespace Sentience\Ai\Schema;

class Schema
{
    public function bool(): BoolType
    {
        return new BoolType();
    }

    public function int(): IntType
    {
        return new IntType();
    }

    public function float(): FloatType
    {
        return new FloatType();
    }

    public function string(): StringType
    {
        return new StringType();
    }

    public function enum(array $values): EnumType
    {
        return new EnumType($values);
    }

    public function array(Schemable|array $items): ArrayType
    {
        return new ArrayType($items);
    }

    public function object(array $properties): ObjectType
    {
        return new ObjectType($properties);
    }
}
