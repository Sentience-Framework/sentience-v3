<?php

declare(strict_types=1);

namespace Sentience\Abstracts;

abstract class Singleton
{
    protected static array $instances = [];

    public static function getInstance(): static
    {
        if (!array_key_exists(static::class, static::$instances)) {
            static::$instances[static::class] = static::createInstance();
        }

        return static::$instances[static::class];
    }

    protected static function createInstance(): static
    {
        return new static();
    }
}
