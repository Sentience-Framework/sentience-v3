<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;

class MySQLDialect extends SQLDialect implements DialectInterface
{
    public const string IDENTIFIER_ESCAPE = '`';
    public const string STRING_ESCAPE = '"';
    public const bool ANSI_ESCAPE = false;

    public function createTable(array $config): QueryWithParams
    {
        $queryWithParams = parent::createTable($config);

        $queryWithParams->query = substr_replace(
            $queryWithParams->query,
            ' ENGINE=InnoDB;',
            -1
        );

        return $queryWithParams;
    }

    public function buildOnConflict(string &$query, array &$params, null|string|array $conflict, ?array $conflictUpdates, ?string $primaryKey, array $insertValues): void
    {
        if (is_null($conflict)) {
            return;
        }

        if (is_null($conflictUpdates) && !$primaryKey) {
            $query = substr_replace($query, 'INSERT IGNORE', 0, 6);

            return;
        }

        $updates = !empty($conflictUpdates) ? $conflictUpdates : $insertValues;

        if ($primaryKey) {
            $lastInsertId = Query::raw(
                sprintf(
                    'LAST_INSERT_ID(%s)',
                    $this->escapeIdentifier($primaryKey)
                )
            );

            $updates = is_null($conflictUpdates)
                ? [$primaryKey => $lastInsertId]
                : [...$updates, $primaryKey => $lastInsertId];
        }

        $query .= sprintf(
            ' ON DUPLICATE KEY UPDATE %s',
            implode(
                ', ',
                array_map(
                    function (mixed $value, string $key) use (&$params): string {
                        if ($value instanceof Raw) {
                            return sprintf(
                                '%s = %s',
                                $this->escapeIdentifier($key),
                                $value->expression
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
        return;
    }

    public function buildColumnDefinition(Column $column): string
    {
        $stringifiedColumn = parent::buildColumnDefinition($column);

        if ($column->autoIncrement && str_contains(strtolower($column->type), 'int')) {
            $stringifiedColumn .= ' AUTO_INCREMENT';
        }

        return $stringifiedColumn;
    }

    public function buildAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        $stringifiedAlterColumn = parent::buildAlterTableAlterColumn($alterColumn);

        return substr_replace($stringifiedAlterColumn, 'MODIFY', 0, 5);
    }

    public function buildAlterTableDropConstraint(DropConstraint $dropConstraint): string
    {
        $stringifiedDropConstraint = parent::buildAlterTableDropConstraint($dropConstraint);

        return substr_replace($stringifiedDropConstraint, 'INDEX', 5, 10);
    }
}
