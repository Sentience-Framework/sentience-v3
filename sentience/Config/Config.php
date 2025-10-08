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
        $pointers = explode('->', $key);

        $reference = &static::$config;

        foreach ($pointers as $pointer) {
            if (!array_key_exists($pointer, $reference) || !is_array($reference[$pointer])) {
                $reference[$pointer] = [];
            }

            $reference = &$reference[$pointer];
        }

        $reference = $value;

        return $value;
    }

    public static function loadFiles(string $path): void
    {
        $configs = Filesystem::scandir($path);

        foreach ($configs as $config) {
            if (!is_file($config)) {
                continue;
            }

            $key = pathinfo($config, PATHINFO_FILENAME);

            static::$config[$key] = include $config;
        }
    }
}
