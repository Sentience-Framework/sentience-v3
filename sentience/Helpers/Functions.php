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

function datetime_from_string(string $dateTimeString): ?DateTime
{
    $timestamp = strtotime($dateTimeString);

    if (!$timestamp) {
        return null;
    }

    $dateTime = new DateTime()->setTimestamp($timestamp);

    $hasMicroseconds = preg_match('/\.([0-9]+)[\+\-]?/', $dateTimeString, $microsecondMatches);

    if ($hasMicroseconds) {
        $microseconds = (int) $microsecondMatches[1];

        $dateTime->setMicrosecond($microseconds);
    }

    $hasTimezoneOffset = preg_match('/[\+\-]([0-9]+)\:?([0-9]*)/', $dateTimeString, $timezoneOffsetMatches);

    if ($hasTimezoneOffset) {
        [$timezoneOffsetHours, $timezoneOffsetMinutes] = array_slice($timezoneOffsetMatches, 1);

        $timezone = timezone_name_from_abbr('', );

        $dateTime->setTimezone(new DateTimeZone($timezone));
    }

    return $dateTime;
}
