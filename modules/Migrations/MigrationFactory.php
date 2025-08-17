<?php

declare(strict_types=1);

namespace Modules\Migrations;

class MigrationFactory
{
    public static function create(array $apply = [], array $rollback = []): string
    {
        $lines = [];

        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'use Modules\Database\Database;';
        $lines[] = 'use Modules\Migrations\MigrationInterface;';
        $lines[] = '';
        $lines[] = 'return new class () implements MigrationInterface {';
        $lines[] = '    public function apply(Database $database): void';
        $lines[] = '    {';

        foreach ($apply as $line) {
            $lines[] = sprintf('        %s', $line);
        }

        $lines[] = '    }';
        $lines[] = '';
        $lines[] = '    public function rollback(Database $database): void';
        $lines[] = '    {';

        foreach ($rollback as $line) {
            $lines[] = sprintf('        %s', $line);
        }

        $lines[] = '    }';
        $lines[] = '};';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }
}
