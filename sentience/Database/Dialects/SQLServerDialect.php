<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;

class SQLServerDialect extends SQLDialect
{
    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        parent::buildConditionRegexOperator(
            $query,
            $params,
            $condition,
            'LIKE',
            'NOT LIKE'
        );
    }

    protected function buildLimit(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        if (!is_null($offset)) {
            return;
        }

        $query = substr_replace(
            $query,
            sprintf(
                'SELECT TOP(%d)',
                $limit
            ),
            0,
            6
        );
    }

    protected function buildOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (in_array(null, [$limit, $offset], true)) {
            return;
        }

        $query .= sprintf(
            'OFFSET %d ROWS FETCH NEXT %d ROWS ONLY',
            $offset,
            $limit
        );
    }

    protected function buildReturning(string &$query, array|null $returning): void
    {
        /**
         * SQL Server supports INSERT INTO (columns) OUTPUT INSERTED.colum
         *
         * Until a nicer solution is found to implement this, SQL Server makes use of Sentience's built-in fallback
         */

        return;
    }

    public function escapeIdentifier(string|array|Raw $identifier, string|null $alias = null): string
    {
        if ($alias) {
            return sprintf(
                '%s AS %s',
                $this->escapeIdentifier($identifier),
                $alias
            );
        }

        if ($identifier instanceof Raw) {
            return (string) $identifier;
        }

        return is_array($identifier)
            ? implode(
                '.',
                array_map(
                    fn (string|array|Raw $identifier): string => $this->escapeIdentifier($identifier),
                    $identifier
                )
            )
            : '[' . Query::escapeAnsi($identifier, ['[', ']']) . ']';
    }
}
