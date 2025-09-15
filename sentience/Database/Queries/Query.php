<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\AliasObject;
use Sentience\Database\Queries\Objects\RawObject;
use Sentience\Database\Queries\Objects\TableWithColumnObject;
use Sentience\Helpers\Strings;
use Sentience\Timestamp\Timestamp;

abstract class Query
{
    public function __construct(protected Database $database, protected DialectInterface $dialect)
    {
    }

    public static function alias(string|array|RawObject $name, string $alias): AliasObject
    {
        return new AliasObject($name, $alias);
    }

    public static function raw(string $expression): RawObject
    {
        return new RawObject($expression);
    }

    public static function tableWithColumn(string|array|AliasObject|RawObject $table, string|AliasObject|RawObject $column): TableWithColumnObject
    {
        return new TableWithColumnObject($table, $column);
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
