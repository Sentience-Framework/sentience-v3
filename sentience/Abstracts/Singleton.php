<?php

namespace sentience\Abstracts;

abstract class Singleton
{
    protected static $instance = null;

    public static function getInstance(): static
    {
        if (!static::$instance) {
            self::$instance = static::createInstance();
        }

        return self::$instance;
    }

    protected static function createInstance(): static
    {
        return new static();
    }
}
