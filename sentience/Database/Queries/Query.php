<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use DateTime;
use Throwable;
use sentience\Database\Database;
use sentience\Database\Dialects\DialectInterface;
use sentience\Database\Queries\Objects\Alias;
use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Queries\Objects\Raw;
use sentience\Database\Queries\Objects\TableWithColumn;
use sentience\Helpers\Strings;

abstract class Query implements QueryInterface
{
    protected string $query;
    protected array $params;

    public function __construct(protected Database $database, protected DialectInterface $dialect)
    {
    }

    public function toRawQuery(): string|array
    {
        return $this->toQueryWithParams()->toRawQuery($this->dialect);
    }

    public function execute(): mixed
    {
        $queryWithParams = $this->toQueryWithParams();

        return $this->database->prepared(
            $queryWithParams->query,
            $queryWithParams->params
        );
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

    public static function now(): DateTime
    {
        return new DateTime();
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
