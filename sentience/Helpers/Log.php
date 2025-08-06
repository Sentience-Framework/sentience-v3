<?php

declare(strict_types=1);

namespace Sentience\Helpers;

use Sentience\Sentience\Stdio;

class Log
{
    public static function stderrBetweenEqualSigns(string $type, array $lines, bool $useCachedWidth = true): void
    {
        $consoleWidth = Console::getWidth($useCachedWidth);

        $equalSigns = (($consoleWidth - strlen($type)) / 2) - 1;

        Stdio::errorFLn(
            '%s %s %s',
            str_repeat('=', (int) ceil($equalSigns)),
            $type,
            str_repeat('=', (int) floor($equalSigns))
        );

        foreach ($lines as $line) {
            Stdio::errorLn($line);
        }

        Stdio::errorLn(str_repeat('=', $consoleWidth));
    }
}
