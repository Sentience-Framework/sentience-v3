<?php

declare(strict_types=1);

use Sentience\Env\Env;

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

function is_cli(): bool
{
    return php_sapi_name() == 'cli';
}
