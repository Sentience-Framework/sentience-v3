<?php

declare(strict_types=1);

namespace Src\Controllers;

use Modules\Abstracts\Controller;
use Modules\Helpers\Console;
use Modules\Helpers\Filesystem;
use Modules\Sentience\Stdio;

class DevToolsController extends Controller
{
    public function sortImports(): void
    {
        $consoleWidth = Console::getWidth();

        $equalSigns = ($consoleWidth - 17) / 2 - 1;

        Stdio::errorFLn(
            '%s Development tools %s',
            str_repeat('=', (int) ceil($equalSigns)),
            str_repeat('=', (int) floor($equalSigns))
        );

        $importRegexPattern = '/^use\s+[^;]+;/m';

        $files = Filesystem::scandir(SENTIENCE_DIR, PHP_INT_MAX);

        $excludedDirectories = [
            Filesystem::path(SENTIENCE_DIR, 'vendor')
        ];

        foreach ($files as $file) {
            if (is_dir($file)) {
                continue;
            }

            foreach ($excludedDirectories as $excludedDirectory) {
                if (str_starts_with((string) $file, $excludedDirectory)) {
                    continue 2;
                }
            }

            if (!str_ends_with((string) $file, '.php')) {
                continue;
            }

            $fileContents = file_get_contents($file);

            $isMatch = preg_match_all($importRegexPattern, $fileContents, $matches);

            if (!$isMatch) {
                continue;
            }

            $imports = $matches[0];

            $globalImports = [];
            $namespaceImports = [];

            foreach ($imports as $import) {
                if (str_contains($import, '\\')) {
                    $namespaceImports[] = $import;

                    continue;
                }

                $globalImports[] = $import;
            }

            natcasesort($globalImports);
            natcasesort($namespaceImports);

            $sortedImports = [...$globalImports, ...$namespaceImports];

            $index = 0;

            file_put_contents(
                $file,
                preg_replace_callback(
                    $importRegexPattern,
                    function () use (&$index, $sortedImports): string {
                        $import = $sortedImports[$index];

                        $index++;

                        return $import;
                    },
                    $fileContents
                )
            );

            Stdio::printFLn('Sorted %d imports in: %s', count($sortedImports), $file);
        }

        Stdio::printLn(str_repeat('=', $consoleWidth));
    }

    public function removeTrailingCommas(): void
    {
        $consoleWidth = Console::getWidth();

        $equalSigns = ($consoleWidth - 17) / 2 - 1;

        Stdio::errorFLn(
            '%s Development tools %s',
            str_repeat('=', (int) ceil($equalSigns)),
            str_repeat('=', (int) floor($equalSigns))
        );

        $files = Filesystem::scandir(SENTIENCE_DIR, PHP_INT_MAX);

        $excludedDirectories = [
            Filesystem::path(SENTIENCE_DIR, 'vendor')
        ];

        foreach ($files as $file) {
            if (is_dir($file)) {
                continue;
            }

            foreach ($excludedDirectories as $excludedDirectory) {
                if (str_starts_with((string) $file, $excludedDirectory)) {
                    continue 2;
                }
            }

            if (!str_ends_with((string) $file, '.php')) {
                continue;
            }

            $fileContents = file_get_contents($file);

            $modifiedFileContents = preg_replace(
                '/,(?=\s*(?=[\)\]\}])(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)/m',
                '',
                $fileContents
            );

            if (is_null($modifiedFileContents)) {
                continue;
            }

            file_put_contents($file, $modifiedFileContents);

            Stdio::printFLn('Removed trailing commas in: %s', $file);
        }

        Stdio::printLn(str_repeat('=', $consoleWidth));
    }

    public function removeExcessiveWhitespace(): void
    {
        $consoleWidth = Console::getWidth();

        $equalSigns = ($consoleWidth - 17) / 2 - 1;

        Stdio::errorFLn(
            '%s Development tools %s',
            str_repeat('=', (int) ceil($equalSigns)),
            str_repeat('=', (int) floor($equalSigns))
        );

        $files = Filesystem::scandir(SENTIENCE_DIR, PHP_INT_MAX);

        $excludedDirectories = [
            Filesystem::path(SENTIENCE_DIR, 'vendor')
        ];

        foreach ($files as $file) {
            if (is_dir($file)) {
                continue;
            }

            foreach ($excludedDirectories as $excludedDirectory) {
                if (str_starts_with((string) $file, $excludedDirectory)) {
                    continue 2;
                }
            }

            if (!str_ends_with((string) $file, '.php')) {
                continue;
            }

            $fileContents = file_get_contents($file);

            $modifiedFileContents = preg_replace(
                '/([\r\n|\r|\n]){3,}(?=(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)/',
                '$1$1',
                $fileContents
            );

            if (is_null($modifiedFileContents)) {
                continue;
            }

            file_put_contents($file, $modifiedFileContents);

            Stdio::printFLn('Removed excessive whitespace in: %s', $file);
        }

        Stdio::printLn(str_repeat('=', $consoleWidth));
    }
}
