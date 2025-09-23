<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Helpers\Strings;
use Sentience\Timestamp\Timestamp;

abstract class Query implements QueryInterface
{
    public function __construct(protected Database $database, protected DialectInterface $dialect, protected string|array|Alias|Raw $table)
    {
    }

    public function toRawQuery(): string|array
    {
        return $this->toQueryWithParams()->toRawQuery($this->dialect);
    }

    public function execute(): mixed
    {
        $queryWithParams = $this->toQueryWithParams();

        return $this->database->queryWithParams($queryWithParams);
    }

    public static function alias(string|array|Raw $name, string $alias): Alias
    {
        return new Alias($name, $alias);
    }

    public static function raw(string $expression): Raw
    {
        return new Raw($expression);
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
