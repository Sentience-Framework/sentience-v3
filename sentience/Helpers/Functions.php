<?php

function env(string $key, mixed $default = null): mixed
{
    if (!array_key_exists($key, $_ENV)) {
        return $default;
    }

    return $_ENV[$key];
}

function is_cli(): bool
{
    return php_sapi_name() == 'cli';
}
