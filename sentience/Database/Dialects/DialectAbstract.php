<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Exceptions\ServerVersionException;

abstract class DialectAbstract implements DialectInterface
{
    protected int $version;

    public function __construct(int|string $version)
    {
        $this->version = $this->version($version);
    }

    protected function version(int|string $version, array $lengths = [10000, 100, 1]): int
    {
        if (is_int($version)) {
            return $version;
        }

        $parts = explode(
            '.',
            strtok(
                $version,
                '-'
            )
        );

        if (count($parts) > count($lengths)) {
            throw new ServerVersionException('not enough lengths defined to parse server version');
        }

        $number = 0;

        foreach ($parts as $index => $part) {
            $number += (int) $part * $lengths[$index];
        }

        return $number;
    }
}
