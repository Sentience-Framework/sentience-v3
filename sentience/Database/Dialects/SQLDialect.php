<?php

namespace Sentience\Database\Dialects;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Objects\AddColumn;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraint;
use Sentience\Database\Queries\Objects\AddPrimaryKeys;
use Sentience\Database\Queries\Objects\AddUniqueConstraint;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Objects\DropColumn;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\OrderBy;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\RenameColumn;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Helpers\Strings;
use Sentience\Timestamp\Timestamp;

class SQLDialect implements DialectInterface
{
    public const string IDENTIFIER_ESCAPE = '"';
    public const string STRING_ESCAPE = "'";
    public const bool ANSI_ESCAPE = true;
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    public const string REGEX_FUNCTION = 'REGEXP';
    public const string NOT_REGEX_FUNCTION = 'NOT REGEXP';

    public function select(array $config): QueryWithParams
    {
        $query = '';
        $params = [];

        $query .= 'SELECT';

        if ($config['distinct']) {
            $query .= ' DISTINCT';
        }

        $query .= ' ';
        $query .= count($config['columns']) > 0
            ? implode(
                ', ',
                array_map(
                    function (string|array|Alias|Raw $column): string {
                        if (is_array($column)) {
                            return $this->escapeIdentifier($column);
                        }

                        if ($column instanceof Alias) {
                            return $this->escapeIdentifierWithAlias($column->name, $column->alias);
                        }

                        if ($column instanceof Raw) {
                            return $column->expression;
                        }

                        return $this->escapeIdentifier($column);
                    },
                    $config['columns']
                )
            )
            : '*';

        $query .= ' FROM';

        $this->buildTable($query, $config['table']);
        $this->buildJoins($query, $config['joins']);
        $this->buildWhere($query, $params, $config['where']);
        $this->buildGroupBy($query, $config['groupBy']);
        $this->buildHaving($query, $params, $config['having']);
        $this->buildOrderBy($query, $config['orderBy']);
        $this->buildLimit($query, $config['limit']);
        $this->buildOffset($query, $config['limit'], $config['offset']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function insert(array $config): QueryWithParams
    {
        if (count($config['values']) == 0) {
            throw new QueryException('no insert values specified');
        }

        $query = '';
        $params = [];

        $query .= 'INSERT INTO';

        $this->buildTable($query, $config['table']);

        $query .= sprintf(
            ' (%s)',
            implode(
                ', ',
                array_map(
                    function (string|array|Alias|Raw $column): string {
                        if ($column instanceof Raw) {
                            return $column->expression;
                        }

                        if ($column instanceof Alias) {
                            return $this->escapeIdentifier($column->name);
                        }

                        return $this->escapeIdentifier($column);
                    },
                    array_keys($config['values'])
                )
            )
        );

        $query .= sprintf(
            ' VALUES (%s)',
            implode(
                ', ',
                array_map(
                    function (mixed $value) use (&$params): string {
                        if ($value instanceof Raw) {
                            return $value->expression;
                        }

                        $params[] = $value;

                        return '?';
                    },
                    $config['values']
                )
            )
        );

        if (array_key_exists('onConflict', $config)) {
            $this->buildOnConflict(
                $query,
                $params,
                $config['onConflict']['conflict'],
                $config['onConflict']['updates'],
                $config['onConflict']['primaryKey'],
                $config['values']
            );
        }

        $this->buildReturning($query, $config['returning']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function update(array $config): QueryWithParams
    {
        if (count($config['values']) == 0) {
            throw new QueryException('no update values specified');
        }

        $query = '';
        $params = [];

        $query .= 'UPDATE';

        $this->buildTable($query, $config['table']);

        $query .= ' SET ';
        $query .= implode(
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
                $config['values'],
                array_keys($config['values'])
            )
        );

        $this->buildWhere($query, $params, $config['where']);
        $this->buildReturning($query, $config['returning']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function delete(array $config): QueryWithParams
    {
        $query = '';
        $params = [];

        $query .= 'DELETE FROM';

        $this->buildTable($query, $config['table']);
        $this->buildWhere($query, $params, $config['where']);
        $this->buildReturning($query, $config['returning']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function createTable(array $config): QueryWithParams
    {
        if (count($config['columns']) == 0) {
            throw new QueryException('no table columns specified');
        }

        if (count($config['primaryKeys']) == 0) {
            throw new QueryException('no table primary key(s) specified');
        }

        $query = '';
        $params = [];

        $query .= 'CREATE TABLE';

        if ($config['ifNotExists']) {
            $query .= ' IF NOT EXISTS';
        }

        $this->buildTable($query, $config['table']);

        $definitions = [];

        foreach ($config['columns'] as $column) {
            $definitions[] = $this->buildColumnDefinition($column);
        }

        $definitions[] = sprintf(
            'PRIMARY KEY (%s)',
            implode(
                ', ',
                array_map(
                    fn (string|Raw $column): string => $this->escapeIdentifier($column),
                    $config['primaryKeys']
                )
            )
        );

        foreach ($config['constraints']['unique'] as $uniqueConstraint) {
            $definitions[] = $this->buildUniqueConstraintDefinition($uniqueConstraint);
        }

        foreach ($config['constraints']['foreignKeys'] as $foreignKeyConstraint) {
            $definitions[] = $this->buildForeignKeyConstraintDefinition($foreignKeyConstraint);
        }

        $query .= sprintf(' (%s)', implode(', ', $definitions));
        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function alterTable(array $config): array
    {
        if (count($config['alters']) == 0) {
            throw new QueryException('no table alters specified');
        }

        return array_map(
            function (object $alter) use ($config): QueryWithParams {
                $query = 'ALTER TABLE';

                $this->buildTable($query, $config['table']);

                $query .= ' ';

                $query .= match (true) {
                    $alter instanceof AddColumn => $this->buildAlterTableAddColumn($alter),
                    $alter instanceof AlterColumn => $this->buildAlterTableAlterColumn($alter),
                    $alter instanceof RenameColumn => $this->buildAlterTableRenameColumn($alter),
                    $alter instanceof DropColumn => $this->buildAlterTableDropColumn($alter),
                    $alter instanceof AddPrimaryKeys => $this->buildAlterTableAddPrimaryKeys($alter),
                    $alter instanceof AddUniqueConstraint => $this->buildAlterTableAddUniqueConstraint($alter),
                    $alter instanceof AddForeignKeyConstraint => $this->buildAlterTableAddForeignKeyConstraint($alter),
                    $alter instanceof DropConstraint => $this->buildAlterTableDropConstraint($alter),
                    default => throw new QueryException('unsupported table alter %s', $alter::class)
                };

                $query .= ';';

                return new QueryWithParams($query);
            },
            $config['alters']
        );
    }

    public function dropTable(array $config): QueryWithParams
    {
        $query = '';
        $params = [];

        $query .= 'DROP TABLE';

        if ($config['ifExists']) {
            $query .= ' IF EXISTS';
        }

        $this->buildTable($query, $config['table']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    protected function buildTable(string &$query, string|array|Alias|Raw $table): void
    {
        $query .= ' ';

        if ($table instanceof Alias) {
            $query .= $this->escapeIdentifierWithAlias($table->name, $table->alias);

            return;
        }

        if ($table instanceof Raw) {
            $query .= $table->expression;

            return;
        }

        $query .= $this->escapeIdentifier($table);
    }

    protected function buildJoins(string &$query, array $joins): void
    {
        if (count($joins) == 0) {
            return;
        }

        $query .= ' ';

        foreach ($joins as $index => $join) {
            if ($index > 0) {
                $query .= ' ';
            }

            if ($join instanceof Raw) {
                $query .= $join->expression;

                continue;
            }

            $query .= sprintf(
                '%s %s ON %s.%s = %s.%s',
                $join->join->value,
                $this->escapeIdentifierWithAlias($join->joinTable, $join->joinTableAlias),
                $this->escapeIdentifier($join->joinTableAlias ?? $join->joinTable),
                $this->escapeIdentifier($join->joinTableColumn),
                $this->escapeIdentifier($join->onTable),
                $this->escapeIdentifier($join->onTableColumn)
            );
        }
    }

    protected function buildWhere(string &$query, array &$params, array $where): void
    {
        if (count($where) == 0) {
            return;
        }

        $query .= ' WHERE ';

        foreach ($where as $index => $condition) {
            $condition instanceof Condition
                ? $this->buildCondition($query, $params, $index, $condition)
                : $this->buildConditionGroup($query, $params, $index, $condition);
        }
    }

    protected function buildCondition(string &$query, array &$params, int $index, Condition $condition): void
    {
        if ($index > 0) {
            $query .= sprintf(' %s ', $condition->chain->value);
        }

        if ($condition->condition == ConditionEnum::RAW) {
            $query .= sprintf('(%s)', $condition->expression);

            array_push($params, ...$condition->value);

            return;
        }

        if (is_null($condition->value)) {
            $query .= sprintf(
                '%s %s',
                $this->escapeIdentifier($condition->expression),
                $condition->condition == ConditionEnum::EQUALS ? 'IS NULL' : 'IS NOT NULL'
            );

            return;
        }

        if (in_array($condition->condition, [ConditionEnum::BETWEEN, ConditionEnum::NOT_BETWEEN])) {
            $query .= sprintf(
                '%s %s ? AND ?',
                $this->escapeIdentifier($condition->expression),
                $condition->condition->value,
                $condition->value[0],
                $condition->value[1]
            );

            array_push($params, ...$condition->value);

            return;
        }

        if (is_array($condition->value)) {
            if (count($condition->value) == 0) {
                $query .= $condition->condition == ConditionEnum::IN ? '1 <> 1' : '1 = 1';

                return;
            }

            $query .= sprintf(
                '%s %s (%s)',
                $this->escapeIdentifier($condition->expression),
                $condition->condition->value,
                implode(', ', array_fill(0, count($condition->value), '?'))
            );

            array_push($params, ...$condition->value);

            return;
        }

        if (in_array($condition->condition, [ConditionEnum::REGEX, ConditionEnum::NOT_REGEX])) {
            $query .= sprintf(
                '%s %s ?',
                $this->escapeIdentifier($condition->expression),
                $condition->condition == ConditionEnum::REGEX ? $this::REGEX_FUNCTION : $this::NOT_REGEX_FUNCTION
            );

            array_push($params, $condition->value);

            return;
        }

        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->expression),
            $condition->condition->value
        );

        array_push($params, $condition->value);
    }

    protected function buildConditionGroup(string &$query, array &$params, int $index, ConditionGroup $group): void
    {
        if ($index > 0) {
            $query .= sprintf(' %s ', $group->chain->value);
        }

        $conditions = $group->getConditions();

        $query .= '(';

        foreach ($conditions as $index => $condition) {
            $condition instanceof Condition
                ? $this->buildCondition($query, $params, $index, $condition)
                : $this->buildConditionGroup($query, $params, $index, $condition);
        }

        $query .= ')';
    }

    protected function buildGroupBy(string &$query, array $groupBy): void
    {
        if (count($groupBy) == 0) {
            return;
        }

        $query .= sprintf(
            ' GROUP BY %s',
            implode(
                ', ',
                array_map(
                    fn (string|array|Raw $column): string => $this->escapeIdentifier($column),
                    $groupBy
                )
            )
        );
    }

    protected function buildHaving(string &$query, array &$params, ?QueryWithParams $having): void
    {
        if (is_null($having)) {
            return;
        }

        $query .= ' HAVING ' . $having->query;

        array_push($params, ...$having->params);
    }

    protected function buildOrderBy(string &$query, array $orderBy): void
    {
        if (count($orderBy) == 0) {
            return;
        }

        $query .= sprintf(
            ' ORDER BY %s',
            implode(
                ', ',
                array_map(
                    fn (OrderBy $orderBy): string => sprintf(
                        '%s %s',
                        $this->escapeIdentifier($orderBy->column),
                        $orderBy->direction->value
                    ),
                    $orderBy
                )
            )
        );
    }

    protected function buildLimit(string &$query, ?int $limit): void
    {
        if (is_null($limit)) {
            return;
        }

        $query .= ' LIMIT ' . $limit;
    }

    protected function buildOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        if (is_null($offset)) {
            return;
        }

        $query .= ' OFFSET ' . $offset;
    }

    protected function buildOnConflict(string &$query, array &$params, null|string|array $conflict, ?array $conflictUpdates, ?string $primaryKey, array $insertValues): void
    {
        /**
         * The official SQL standard does not define a clear way to handle conflicts
         */

        return;
    }

    protected function buildReturning(string &$query, ?array $returning): void
    {
        if (is_null($returning)) {
            return;
        }

        $columns = empty($returning)
            ? '*'
            : implode(
                ', ',
                array_map(
                    fn (string $column): string => $this->escapeIdentifier($column),
                    $returning
                )
            );

        $query .= ' RETURNING ' . $columns;
    }

    protected function buildColumnDefinition(Column $column): string
    {
        $stringifiedColumn = sprintf(
            '%s %s',
            $this->escapeIdentifier($column->name),
            $column->type
        );

        if ($column->notNull) {
            $stringifiedColumn .= ' NOT NULL';
        }

        if (!is_null($column->defaultValue) && !$column->autoIncrement) {
            $defaultValue = preg_match('/^.*\(.*\)$/', (string) $column->defaultValue)
                ? (string) $column->defaultValue
                : $this->castToQuery($column->defaultValue);

            $stringifiedColumn .= ' DEFAULT ' . $defaultValue;
        }

        return $stringifiedColumn;
    }

    protected function buildUniqueConstraintDefinition(UniqueConstraint $uniqueConstraint): string
    {
        $stringifiedUniqueConstraint = sprintf(
            'UNIQUE (%s)',
            implode(
                ', ',
                array_map(
                    fn (string $column): string => $this->escapeIdentifier($column),
                    $uniqueConstraint->columns
                )
            )
        );

        if ($uniqueConstraint->name) {
            return sprintf(
                'CONSTRAINT %s %s',
                $this->escapeIdentifier($uniqueConstraint->name),
                $stringifiedUniqueConstraint
            );
        }

        return $stringifiedUniqueConstraint;
    }

    protected function buildForeignKeyConstraintDefinition(ForeignKeyConstraint $foreignKeyConstraint): string
    {
        $stringifiedForeignKeyConstraint = sprintf(
            'FOREIGN KEY (%s) REFERENCES %s (%s)',
            $foreignKeyConstraint->column,
            $foreignKeyConstraint->referenceTable,
            $foreignKeyConstraint->referenceColumn
        );

        if ($foreignKeyConstraint->name) {
            return sprintf(
                'CONSTRAINT %s %s',
                $this->escapeIdentifier($foreignKeyConstraint->name),
                $stringifiedForeignKeyConstraint
            );
        }

        return $stringifiedForeignKeyConstraint;
    }

    protected function buildAlterTableAddColumn(AddColumn $addColumn): string
    {
        return sprintf(
            'ADD COLUMN %s',
            $this->buildColumnDefinition($addColumn)
        );
    }

    protected function buildAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        return sprintf(
            'ALTER COLUMN %s %s',
            $this->escapeIdentifier($alterColumn->column),
            $alterColumn->options
        );
    }

    protected function buildAlterTableRenameColumn(RenameColumn $renameColumn): string
    {
        return sprintf(
            'RENAME COLUMN %s TO %s',
            $this->escapeIdentifier($renameColumn->oldName),
            $this->escapeIdentifier($renameColumn->newName)
        );
    }

    protected function buildAlterTableDropColumn(DropColumn $dropColumn): string
    {
        return sprintf(
            'DROP COLUMN %s',
            $this->escapeIdentifier($dropColumn->column)
        );
    }

    protected function buildAlterTableAddPrimaryKeys(AddPrimaryKeys $addPrimaryKeys): string
    {
        return sprintf(
            'ADD PRIMARY KEY (%s)',
            implode(
                ', ',
                array_map(
                    fn (string|array|Raw $column): string => $this->escapeIdentifier($column),
                    $addPrimaryKeys->columns
                )
            )
        );
    }

    protected function buildAlterTableAddUniqueConstraint(AddUniqueConstraint $addUniqueConstraint): string
    {
        return sprintf(
            'ADD %s',
            $this->buildUniqueConstraintDefinition($addUniqueConstraint)
        );
    }

    protected function buildAlterTableAddForeignKeyConstraint(AddForeignKeyConstraint $addForeignKeyConstraint): string
    {
        return sprintf(
            'ADD %s',
            $this->buildForeignKeyConstraintDefinition($addForeignKeyConstraint)
        );
    }

    protected function buildAlterTableDropConstraint(DropConstraint $dropConstraint): string
    {
        return sprintf(
            'DROP CONSTRAINT %s',
            $this->escapeIdentifier($dropConstraint->constraint)
        );
    }

    protected function escapeIdentifierWithAlias(string|array|Raw $identifier, ?string $alias): string
    {
        $escapedIdentifier = $this->escapeIdentifier($identifier);

        if (!$alias) {
            return $escapedIdentifier;
        }

        return sprintf('%s AS %s', $escapedIdentifier, $this->escapeIdentifier($alias));
    }

    public function escapeIdentifier(string|array|Raw $identifier): string
    {
        if ($identifier instanceof Raw) {
            return $identifier->expression;
        }

        return is_array($identifier)
            ? implode(
                '.',
                array_map(
                    fn (string|Raw $identifier): string => $this->escapeIdentifier($identifier),
                    $identifier
                )
            )
            : $this->escape($identifier, $this::IDENTIFIER_ESCAPE);
    }

    public function escapeString(string $string): string
    {
        return $this->escape($string, $this::STRING_ESCAPE);
    }

    protected function escape(string $string, string $char): string
    {
        $escapedString = $this::ANSI_ESCAPE
            ? Strings::escapeChars($string, [$char], '$0$0', '/%s/')
            : Strings::escapeChars($string, ['\\', $char]);

        return $char . $escapedString . $char;
    }

    public function castToDriver(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $this->castBool($value);
        }

        if ($value instanceof DateTimeInterface) {
            return $this->castTimestamp($value);
        }

        return $value;
    }

    public function castToQuery(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->escapeString($value);
        }

        if (is_bool($value)) {
            $bool = $this->castBool($value);

            return is_string($bool)
                ? $this->escapeString($bool)
                : $bool;
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if ($value instanceof DateTimeInterface) {
            return $this->escapeString($this->castTimestamp($value));
        }

        return $value;
    }

    public function castBool(bool $bool): mixed
    {
        return $bool ? 1 : 0;
    }

    public function castTimestamp(DateTimeInterface $dateTimeInterface): mixed
    {
        return $dateTimeInterface->format($this::DATETIME_FORMAT);
    }

    public function parseBool(mixed $value): bool
    {
        return $value == 1 ? true : false;
    }

    public function parseTimestamp(string $string): ?Timestamp
    {
        $timestamp = Timestamp::createFromFormat($this::DATETIME_FORMAT, $string);

        if ($timestamp) {
            return $timestamp;
        }

        return Timestamp::createFromString($string);
    }

    public function phpTypeToColumnType(string $type, bool $autoIncrement, bool $isPrimaryKey, bool $inConstraint): string
    {
        return match ($type) {
            'bool' => 'INT',
            'int' => 'INT',
            'float' => 'FLOAT',
            'string' => 'TEXT',
            Timestamp::class,
            DateTime::class,
            DateTimeImmutable::class => 'DATETIME',
            default => 'TEXT'
        };
    }
}
