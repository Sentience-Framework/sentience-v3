<?php

use Sentience\Config\Config;
use Sentience\Env\Env;
use Sentience\Helpers\Log;
use Sentience\Sentience\Stdio;

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

function breakpoint(array $vars, int $sleep = 5): void
{
    $lines = [];

    foreach ($vars as $key => $value) {
        $lines[] = sprintf('Variable: $%s', $key);
        $lines[] = sprintf('Value:    %s', (string) print_r($value, true));
    }

    Log::stderrBetweenEqualSigns('Breakpoint', $lines);

    if (!is_cli()) {
        Stdio::errorFLn('Waiting %d seconds before moving on to the next breakpoint', $sleep);

        sleep($sleep);

        return;
    }

    Stdio::readLine('Press enter to continue');
}
