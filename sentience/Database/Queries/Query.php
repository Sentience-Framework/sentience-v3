<?php

namespace sentience\Database\queries;

use DateTime;
use sentience\Helpers\Strings;
use Throwable;
use sentience\Database\Database;
use sentience\Database\dialects\DialectInterface;
use sentience\Database\queries\objects\Alias;
use sentience\Database\queries\objects\Raw;
use sentience\Database\Results;

abstract class Query implements QueryInterface
{
    protected Database $database;
    protected DialectInterface $dialect;
    protected string $query;
    protected array $params;

    public function __construct(Database $database, DialectInterface $dialect)
    {
        $this->database = $database;
        $this->dialect = $dialect;
    }

    public function execute(): ?Results
    {
        $queryWithParams = $this->build();

        if (preg_match('/^CREATE|ALTER|DROP/', $queryWithParams->expression)) {
            $this->database->unsafe($queryWithParams->expression);

            return null;
        }

        return $this->database->safe(
            $queryWithParams->expression,
            $queryWithParams->params
        );
    }

    public function tryCatch(?callable $handleException = null): ?Results
    {
        try {
            return $this->execute();
        } catch (Throwable $exception) {
            if ($handleException) {
                $handleException($exception);
            }

            return null;
        }
    }

    public function toRawQuery(): string
    {
        return $this->build()->toRawQuery($this->dialect);
    }

    public static function alias(string|array|Raw $name, string $alias): Alias
    {
        return new Alias($name, $alias);
    }

    public static function raw(string $expression): Raw
    {
        return new Raw($expression);
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
