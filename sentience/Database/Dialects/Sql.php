<?php

namespace Sentience\Database\Dialects;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\Operator;
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

class Sql implements DialectInterface
{
    public const string IDENTIFIER_ESCAPE = '"';
    public const string STRING_ESCAPE = "'";
    public const bool ANSI_ESCAPE = true;
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    public const string REGEX_FUNCTION = 'REGEXP';
    public const string NOT_REGEX_FUNCTION = 'NOT REGEXP';

    public function select(array $config): QueryWithParams
    {
        if (!$config['table']) {
            throw new QueryException('no table specified');
        }

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

        $this->addTable($query, $config['table']);
        $this->addJoins($query, $config['joins']);
        $this->addWhere($query, $params, $config['where']);
        $this->addGroupBy($query, $config['groupBy']);
        $this->addHaving($query, $params, $config['having']);
        $this->addOrderBy($query, $config['orderBy']);
        $this->addLimit($query, $config['limit']);
        $this->addOffset($query, $config['limit'], $config['offset']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function insert(array $config): QueryWithParams
    {
        if (!$config['table']) {
            throw new QueryException('no table specified');
        }

        if (count($config['values']) == 0) {
            throw new QueryException('no insert values specified');
        }

        $query = '';
        $params = [];

        $query .= 'INSERT INTO';

        $this->addTable($query, $config['table']);

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
            $this->addOnConflict(
                $query,
                $params,
                $config['onConflict']['conflict'],
                $config['onConflict']['updates'],
                $config['onConflict']['primaryKey'],
                $config['values']
            );
        }

        $this->addReturning($query, $config['returning']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function update(array $config): QueryWithParams
    {
        if (!$config['table']) {
            throw new QueryException('no table specified');
        }

        if (count($config['values']) == 0) {
            throw new QueryException('no update values specified');
        }

        $query = '';
        $params = [];

        $query .= 'UPDATE';

        $this->addTable($query, $config['table']);

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

        $this->addWhere($query, $params, $config['where']);
        $this->addReturning($query, $config['returning']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function delete(array $config): QueryWithParams
    {
        if (!$config['table']) {
            throw new QueryException('no table specified');
        }

        $query = '';
        $params = [];

        $query .= 'DELETE FROM';

        $this->addTable($query, $config['table']);
        $this->addWhere($query, $params, $config['where']);
        $this->addReturning($query, $config['returning']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function createTable(array $config): QueryWithParams
    {
        if (!$config['table']) {
            throw new QueryException('no table specified');
        }

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

        $this->addTable($query, $config['table']);

        $definitions = [];

        foreach ($config['columns'] as $column) {
            $definitions[] = $this->stringifyColumnDefinition($column);
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
            $definitions[] = $this->stringifyUniqueConstraintDefinition($uniqueConstraint);
        }

        foreach ($config['constraints']['foreignKey'] as $foreignKeyConstraint) {
            $definitions[] = $this->stringifyForeignKeyConstraintDefinition($foreignKeyConstraint);
        }

        $query .= sprintf(' (%s)', implode(', ', $definitions));
        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function alterTable(array $config): array
    {
        if (!$config['table']) {
            throw new QueryException('no table specified');
        }

        if (count($config['alters']) == 0) {
            throw new QueryException('no table alters specified');
        }

        return array_map(
            function (object $alter) use ($config): QueryWithParams {
                $query = 'ALTER TABLE';

                $this->addTable($query, $config['table']);

                $query .= ' ';

                $query .= match (true) {
                    $alter instanceof AddColumn => $this->stringifyAlterTableAddColumn($alter),
                    $alter instanceof AlterColumn => $this->stringifyAlterTableAlterColumn($alter),
                    $alter instanceof RenameColumn => $this->stringifyAlterTableRenameColumn($alter),
                    $alter instanceof DropColumn => $this->stringifyAlterTableDropColumn($alter),
                    $alter instanceof AddPrimaryKeys => $this->stringifyAlterTableAddPrimaryKeys($alter),
                    $alter instanceof AddUniqueConstraint => $this->stringifyAlterTableAddUniqueConstraint($alter),
                    $alter instanceof AddForeignKeyConstraint => $this->stringifyAlterTableAddForeignKeyConstraint($alter),
                    $alter instanceof DropConstraint => $this->stringifyAlterTableDropConstraint($alter),
                    default => throw new QueryException('unsupported alter %s', $alter::class)
                };

                $query .= ';';

                return new QueryWithParams($query);
            },
            $config['alters']
        );
    }

    public function dropTable(array $config): QueryWithParams
    {
        if (!$config['table']) {
            throw new QueryException('no table specified');
        }

        $query = '';
        $params = [];

        $query .= 'DROP TABLE';

        if ($config['ifExists']) {
            $query .= ' IF EXISTS';
        }

        $this->addTable($query, $config['table']);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    protected function addTable(string &$query, string|array|Alias|Raw $table): void
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

    protected function addJoins(string &$query, array $joins): void
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
                $join->type->value,
                $this->escapeIdentifierWithAlias($join->joinTable, $join->joinTableAlias),
                $this->escapeIdentifier($join->joinTableAlias ?? $join->joinTable),
                $this->escapeIdentifier($join->joinTableColumn),
                $this->escapeIdentifier($join->onTable),
                $this->escapeIdentifier($join->onTableColumn)
            );
        }
    }

    protected function addWhere(string &$query, array &$params, array $where): void
    {
        if (count($where) == 0) {
            return;
        }

        $query .= ' WHERE ';

        foreach ($where as $index => $condition) {
            $condition instanceof Condition
                ? $this->addCondition($query, $params, $index, $condition)
                : $this->addConditionGroup($query, $params, $index, $condition);
        }
    }

    protected function addCondition(string &$query, array &$params, int $index, Condition $condition): void
    {
        if ($index > 0) {
            $query .= sprintf(' %s ', $condition->chain->value);
        }

        if ($condition->type == Operator::RAW) {
            $query .= sprintf('(%s)', $condition->expression);

            array_push($params, ...$condition->value);

            return;
        }

        if (is_null($condition->value)) {
            $query .= sprintf(
                '%s %s',
                $this->escapeIdentifier($condition->expression),
                $condition->type == Operator::EQUALS ? 'IS NULL' : 'IS NOT NULL'
            );

            return;
        }

        if (in_array($condition->type, [Operator::BETWEEN, Operator::NOT_BETWEEN])) {
            $query .= sprintf(
                '%s %s ? AND ?',
                $this->escapeIdentifier($condition->expression),
                $condition->type->value,
                $condition->value[0],
                $condition->value[1]
            );

            array_push($params, ...$condition->value);

            return;
        }

        if (is_array($condition->value)) {
            if (count($condition->value) == 0) {
                $query .= $condition->type == Operator::IN ? '1 <> 1' : '1 = 1';

                return;
            }

            $query .= sprintf(
                '%s %s (%s)',
                $this->escapeIdentifier($condition->expression),
                $condition->type->value,
                implode(', ', array_fill(0, count($condition->value), '?'))
            );

            array_push($params, ...$condition->value);

            return;
        }

        if (in_array($condition->type, [Operator::REGEX, Operator::NOT_REGEX])) {
            $query .= sprintf(
                '%s %s ?',
                $this->escapeIdentifier($condition->expression),
                $condition->type == Operator::REGEX ? $this::REGEX_FUNCTION : $this::NOT_REGEX_FUNCTION
            );

            array_push($params, $condition->value);

            return;
        }

        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->expression),
            $condition->type->value
        );

        array_push($params, $condition->value);
    }

    protected function addConditionGroup(string &$query, array &$params, int $index, ConditionGroup $group): void
    {
        if ($index > 0) {
            $query .= sprintf(' %s ', $group->chain->value);
        }

        $conditions = $group->getConditions();

        $query .= '(';

        foreach ($conditions as $index => $condition) {
            $condition instanceof Condition
                ? $this->addCondition($query, $params, $index, $condition)
                : $this->addConditionGroup($query, $params, $index, $condition);
        }

        $query .= ')';
    }

    protected function addGroupBy(string &$query, array $groupBy): void
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

    protected function addHaving(string &$query, array &$params, ?QueryWithParams $having): void
    {
        if (is_null($having)) {
            return;
        }

        $query .= ' HAVING ' . $having->query;

        array_push($params, ...$having->params);
    }

    protected function addOrderBy(string &$query, array $orderBy): void
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

    protected function addLimit(string &$query, ?int $limit): void
    {
        if (is_null($limit)) {
            return;
        }

        $query .= ' LIMIT ' . $limit;
    }

    protected function addOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        if (is_null($offset)) {
            return;
        }

        $query .= ' OFFSET ' . $offset;
    }

    protected function addOnConflict(string &$query, array &$params, null|string|array $conflict, ?array $conflictUpdates, ?string $primaryKey, array $insertValues): void
    {
        /**
         * The official SQL standard does not define a clear way to handle conflicts
         */

        return;
    }

    protected function addReturning(string &$query, ?array $returning): void
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

    protected function stringifyColumnDefinition(Column $column): string
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

    protected function stringifyUniqueConstraintDefinition(UniqueConstraint $uniqueConstraint): string
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

    protected function stringifyForeignKeyConstraintDefinition(ForeignKeyConstraint $foreignKeyConstraint): string
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

    protected function stringifyAlterTableAddColumn(AddColumn $addColumn): string
    {
        return sprintf(
            'ADD COLUMN %s',
            $this->stringifyColumnDefinition($addColumn)
        );
    }

    protected function stringifyAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        return sprintf(
            'ALTER COLUMN %s %s',
            $this->escapeIdentifier($alterColumn->column),
            $alterColumn->options
        );
    }

    protected function stringifyAlterTableRenameColumn(RenameColumn $renameColumn): string
    {
        return sprintf(
            'RENAME COLUMN %s TO %s',
            $this->escapeIdentifier($renameColumn->oldName),
            $this->escapeIdentifier($renameColumn->newName)
        );
    }

    protected function stringifyAlterTableDropColumn(DropColumn $dropColumn): string
    {
        return sprintf(
            'DROP COLUMN %s',
            $this->escapeIdentifier($dropColumn->column)
        );
    }

    protected function stringifyAlterTableAddPrimaryKeys(AddPrimaryKeys $addPrimaryKeys): string
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

    protected function stringifyAlterTableAddUniqueConstraint(AddUniqueConstraint $addUniqueConstraint): string
    {
        return sprintf(
            'ADD %s',
            $this->stringifyUniqueConstraintDefinition($addUniqueConstraint)
        );
    }

    protected function stringifyAlterTableAddForeignKeyConstraint(AddForeignKeyConstraint $addForeignKeyConstraint): string
    {
        return sprintf(
            'ADD %s',
            $this->stringifyForeignKeyConstraintDefinition($addForeignKeyConstraint)
        );
    }

    protected function stringifyAlterTableDropConstraint(DropConstraint $dropConstraint): string
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
