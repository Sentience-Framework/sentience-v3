<?php



use Modules\Env\Env;

function env(?string $key = null, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

function is_cli(): bool
{
    return php_sapi_name() == 'cli';
}

function cast(null|bool|int|float|string $value, string $to = 'string'): mixed
{
    return match ($to) {
        'bool' => (bool) $value,
        'int' => (int) $value,
        'float' => (float) $value,
        'string' => (string) $value,
        default => $value
    };
}
