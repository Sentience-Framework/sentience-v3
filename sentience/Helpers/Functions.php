<?php

use Sentience\Config\Config;
use Sentience\Env\Env;

function env(null|string|array $key = null, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

function config(null|string|array $key = null, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function is_cli(): bool
{
    return php_sapi_name() == 'cli';
}
