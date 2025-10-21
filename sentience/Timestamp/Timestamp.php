<?php

namespace Sentience\Timestamp;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use Throwable;

class Timestamp extends DateTime implements JsonSerializable
{
    public const string JSON = 'Y-m-d\TH:i:s.v\Z';

    public static function now(): static
    {
        return new static();
    }

    public static function createFromString(string $string): bool|static
    {
        try {
            return new static($string);
        } catch (Throwable $exception) {
        }

        $timestamp = strtotime($string);

        if (!$timestamp) {
            return false;
        }

        $hasMicroseconds = preg_match('/\.([0-9]{0,6})[\+\-]?/', $string, $microsecondsMatches);

        $instance = static::createFromFormat(
            'U.u',
            sprintf(
                '%d.%d',
                $timestamp,
                $hasMicroseconds ? (int) $microsecondsMatches[1] : 0
            )
        );

        if (!$instance) {
            return false;
        }

        $hasTimezoneOffset = preg_match('/([\+\-])([0-9]+)\:?([0-9]*)$/', $string, $timezoneOffsetMatches);

        if ($hasTimezoneOffset) {
            [$modifier, $timezoneOffsetHours, $timezoneOffsetMinutes] = array_slice($timezoneOffsetMatches, 1);

            $multiplier = ((int) $timezoneOffsetHours + (int) $timezoneOffsetMinutes / 60) * ($modifier == '+' ? 1 : -1);

            $timezone = timezone_name_from_abbr('', (int) ($multiplier * 3600));

            if ($timezone) {
                $instance->setTimezone(new DateTimeZone($timezone));
            }
        }

        return $instance;
    }

    public static function createFromTimestamp(int|float $timestamp): static
    {
        return static::createFromFormat(
            str_contains((string) $timestamp, '.') ? 'U.u' : 'U',
            (string) $timestamp
        );
    }

    public function toDateTime(): DateTime
    {
        return DateTime::createFromInterface($this);
    }

    public function toDateTimeImmutable(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($this);
    }

    public function addSeconds(int $seconds): static
    {
        return $this->add(DateInterval::createFromDateString("{$seconds} seconds"));
    }

    public function addMinutes(int $minutes): static
    {
        return $this->add(DateInterval::createFromDateString("{$minutes} minutes"));
    }

    public function addHours(int $hours): static
    {
        return $this->add(DateInterval::createFromDateString("{$hours} hours"));
    }

    public function addDays(int $days): static
    {
        return $this->add(DateInterval::createFromDateString("{$days} days"));
    }

    public function addWeeks(int $weeks): static
    {
        return $this->add(DateInterval::createFromDateString("{$weeks} weeks"));
    }

    public function addMonths(int $months): static
    {
        return $this->add(DateInterval::createFromDateString("{$months} months"));
    }

    public function addYears(int $years): static
    {
        return $this->add(DateInterval::createFromDateString("{$years} years"));
    }

    public function jsonSerialize(): string
    {
        return (clone $this)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(static::JSON);
    }
}
