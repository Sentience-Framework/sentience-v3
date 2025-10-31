<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Objects\Condition;

class FirebirdDialect extends SQLDialect
{
    protected const bool RETURNING = true;

    protected function buildLimit(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        if (!is_null($offset)) {
            return;
        }

        $query .= ' ROWS ' . $limit;
    }

    protected function buildOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit) || is_null($offset)) {
            return;
        }

        $rows = $offset + 1;
        $to = $rows + $limit - 1;

        $query .= sprintf(
            ' ROWS %d TO %d',
            $rows,
            $to
        );
    }

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition == ConditionEnum::REGEX ? 'SIMILAR TO' : 'NOT SIMILAR TO'
        );

        [$pattern, $flags] = $condition->value;

        array_push(
            $params,
            !empty($flags)
            ? sprintf(
                '(?%s)%s',
                $flags,
                $pattern
            ) : $pattern
        );
    }

    public function castToQuery(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return parent::castToQuery($value);
    }

    public function castBool(bool $bool): bool
    {
        return $bool;
    }

    public function parseBool(mixed $bool): bool
    {
        return $bool;
    }
}
