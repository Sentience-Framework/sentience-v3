<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Query;

class SQLServerDialect extends SQLDialect
{
    protected const string ESCAPE_IDENTIFIER = '[]';

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
         * SQL Server relies on Sentience's returning fallback
         */

        return;
    }

    protected function escape(string $string, string $char): string
    {
        if ($char == static::ESCAPE_IDENTIFIER) {
            return '[' . Query::escapeAnsi($string, ['[', ']']) . ']';
        }

        return parent::escape($string, $char);
    }
}
