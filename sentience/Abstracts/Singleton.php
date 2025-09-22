<?php

namespace Sentience\Abstracts;

abstract class Singleton
{
    private static array $instances = [];

    public static function getInstance(): static
    {
        if (!array_key_exists(static::class, self::$instances)) {
            self::$instances[static::class] = static::createInstance();
        }

        return self::$instances[static::class];
    }

    protected static function createInstance(): static
    {
        return new static();
    }
}
