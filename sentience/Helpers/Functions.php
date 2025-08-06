<?php

declare(strict_types=1);

use sentience\Database\Queries\Query;
use sentience\Env\Env;

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

function is_cli(): bool
{
    return php_sapi_name() == 'cli';
}
