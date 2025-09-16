<?php

namespace Sentience\Config;

use Sentience\Helpers\Filesystem;

class Config
{
    protected static array $config = [];

    public static function get(null|string|array $key = null, mixed $default = null): mixed
    {
        if (!$key) {
            return static::$config;
        }

        $keys = (array) $key;

        foreach ($keys as $key) {
            $pointers = explode('->', $key);

            $current = (array) static::$config;

            foreach ($pointers as $pointer) {
                $current = (array) $current;

                if (!key_exists($pointer, $current)) {
                    continue 2;
                }

                $current = $current[$pointer];
            }

            return $current;
        }

        return $default;
    }

    public static function set(string $key, mixed $value): mixed
    {
        return static::$config[$key] = $value;
    }

    public static function loadDirectory(string $path): void
    {
        $configs = Filesystem::scandir($path);

        foreach ($configs as $config) {
            $key = strtok(
                basename($config),
                '.'
            );

            static::$config[$key] = include $config;
        }
    }
}
