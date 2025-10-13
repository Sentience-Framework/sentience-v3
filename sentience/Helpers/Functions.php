<?php

use Sentience\Config\Config;
use Sentience\Env\Env;
use Sentience\Helpers\Log;
use Sentience\Sentience\DependencyInjector;
use Sentience\Sentience\Stdio;
use Sentience\Timestamp\Timestamp;

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

function now(?DateTimeZone $timezone = null): Timestamp
{
    return new Timestamp('now', $timezone);
}

function breakpoint(array $vars, ?callable $encodeVar = null): void
{
    $lines = [];

    foreach ($vars as $key => $value) {
        $lines[] = sprintf(
            '$%s = %s',
            $key,
            $encodeVar ? $encodeVar($value) : print_r($value, true)
        );
    }

    Log::stderrBetweenEqualSigns('Breakpoint', $lines);

    if (!is_cli()) {
        throw new Exception('unable to use breakpoint in http request');
    }

    Stdio::readLine('Press enter to continue');
}

function inject(string $class, array $injectables = []): object
{
    $dependencyInjector = DependencyInjector::getInstance();

    $reflectionClass = new ReflectionClass($class);

    $constructorReflectionMethod = $reflectionClass->getConstructor();

    $parameters = $constructorReflectionMethod
        ? $dependencyInjector->getFunctionParameters(
            $constructorReflectionMethod,
            $injectables
        )
        : [];

    return new $class(...$parameters);
}
