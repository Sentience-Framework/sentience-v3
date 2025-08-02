<?php

declare(strict_types=1);

namespace sentience\Abstracts;

abstract class Singleton
{
    protected static $instance = null;

    public static function getInstance(): static
    {
        if (!static::$instance) {
            static::$instance = static::createInstance();
        }

        return static::$instance;
    }

    protected static function createInstance(): static
    {
        return new static();
    }
}
