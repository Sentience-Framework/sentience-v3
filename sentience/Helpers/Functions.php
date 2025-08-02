<?php

declare(strict_types=1);

use sentience\Environment\Environment;

function env(string $key, mixed $default = null): mixed
{
    return Environment::get($key, $default);
}

function is_cli(): bool
{
    return php_sapi_name() == 'cli';
}
