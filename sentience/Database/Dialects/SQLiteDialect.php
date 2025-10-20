<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraint;
use Sentience\Database\Queries\Objects\AddPrimaryKeys;
use Sentience\Database\Queries\Objects\AddUniqueConstraint;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\Raw;

class SQLiteDialect extends SQLDialect
{
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    public const bool ON_CONFLICT = true;
    public const bool RETURNING = true;

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition == ConditionEnum::REGEX ? 'REGEXP' : 'NOT REGEXP'
        );

        array_push($params, $condition->value);

        return;
    }

    public function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values): void
    {
        if (is_null($onConflict)) {
            return;
        }

        if (is_string($onConflict->conflict)) {
            throw new QueryException('SQLite does not support named constraints. Please use an array of columns');
        }

        $conflict = sprintf(
            '(%s)',
            implode(
                ', ',
                array_map(
                    fn (string|Raw $column): string => $this->escapeIdentifier($column),
                    $onConflict->conflict
                )
            )
        );

        if (is_null($onConflict->updates)) {
            $query .= sprintf(' ON CONFLICT %s DO NOTHING', $conflict);

            return;
        }

        $updates = count($onConflict->updates) > 0 ? $onConflict->updates : $values;

        $query .= sprintf(
            ' ON CONFLICT %s DO UPDATE SET %s',
            $conflict,
            implode(
                ', ',
                array_map(
                    function (mixed $value, string $key) use (&$params): string {
                        if ($value instanceof Raw) {
                            return sprintf(
                                '%s = %s',
                                $this->escapeIdentifier($key),
                                (string) $value
                            );
                        }

                        $params[] = $value;

                        return sprintf('%s = ?', $this->escapeIdentifier($key));
                    },
                    $updates,
                    array_keys($updates)
                )
            )
        );
    }

    public function buildReturning(string &$query, ?array $returning): void
    {
        if ($this->version < 33500) {
            return;
        }

        parent::buildReturning($query, $returning);
    }

    public function buildAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        throw new QueryException('SQLite does not support altering columns');
    }

    protected function buildAlterTableAddPrimaryKeys(AddPrimaryKeys $addPrimaryKeys): string
    {
        throw new QueryException('SQLite does not support adding primary keys by altering the table');
    }

    protected function buildAlterTableAddUniqueConstraint(AddUniqueConstraint $addUniqueConstraint): string
    {
        throw new QueryException('SQLite does not support adding constraints by altering the table');
    }

    protected function buildAlterTableAddForeignKeyConstraint(AddForeignKeyConstraint $addForeignKeyConstraint): string
    {
        throw new QueryException('SQLite does not support adding constraints by altering the table');
    }

    protected function buildAlterTableDropConstraint(DropConstraint $dropConstraint): string
    {
        throw new QueryException('SQLite does not support dropping constraints by altering the table');
    }
}
