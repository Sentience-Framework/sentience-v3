<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Objects\Alias;
use Modules\Database\Queries\Objects\Raw;
use Modules\Database\Queries\Objects\TableWithColumn;
use Modules\Helpers\Strings;
use Modules\Timestamp\Timestamp;

abstract class Query
{
    public function __construct(protected Database $database, protected DialectInterface $dialect)
    {
    }

    public static function alias(string|array|Raw $name, string $alias): Alias
    {
        return new Alias($name, $alias);
    }

    public static function raw(string $expression): Raw
    {
        return new Raw($expression);
    }

    public static function tableWithColumn(string|array|Alias|Raw $table, string|Alias|Raw $column): TableWithColumn
    {
        return new TableWithColumn($table, $column);
    }

    public static function now(): Timestamp
    {
        return new Timestamp();
    }

    public static function escapeLikeChars(string $string, bool $escapeBackslash = false): string
    {
        $chars = ['%', '_', '-', '^', '[', ']'];

        if ($escapeBackslash) {
            array_unshift($chars, '\\');
        }

        return Strings::escapeChars($string, $chars);
    }
}
