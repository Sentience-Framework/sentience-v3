<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\Query;

class PgSQLSchema extends SchemaAbstract
{
    public function tables(): array
    {
        $tables = $this->database->select(['information_schema', 'tables'])
            ->columns(['table_name'])
            ->whereEquals('table_schema', Query::raw('current_schema()'))
            ->whereEquals('table_type', 'BASE TABLE')
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'table_name');
    }

    public function columns(string|array|Sql $table): array
    {
        $type = function (string $type, ?int $size): string|Type {
            return match (strtoupper($type)) {
                'BOOLEAN', 'BOOL' => new Type(TypeEnum::Bool),
                'SMALLINT', 'INT2' => new Type(TypeEnum::Int, 16),
                'INTEGER', 'INT4', 'INT' => new Type(TypeEnum::Int, 32),
                'BIGINT', 'INT8' => new Type(TypeEnum::Int, 64),
                'REAL', 'FLOAT4' => new Type(TypeEnum::Float, 32),
                'DOUBLE PRECISION', 'FLOAT8', 'FLOAT' => new Type(TypeEnum::Float, 64),
                'NUMERIC', 'DECIMAL' => new Type(TypeEnum::Float, $size),
                'CHARACTER VARYING', 'VARCHAR' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
                'CHARACTER', 'CHAR', 'BPCHAR' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
                'TEXT' => new Type(TypeEnum::String, PHP_INT_MAX),
                'NAME' => new Type(TypeEnum::String, 64),
                'OID' => new Type(TypeEnum::Int, 32),
                'CID' => new Type(TypeEnum::Int, 32),
                'XMLOID' => new Type(TypeEnum::Int, 32),
                'JSON' => new Type(TypeEnum::String, PHP_INT_MAX),
                'JSONB' => new Type(TypeEnum::String, PHP_INT_MAX),
                'UUID' => new Type(TypeEnum::String, PHP_INT_MAX),
                'XML' => new Type(TypeEnum::String, PHP_INT_MAX),
                'BYTEA' => new Type(TypeEnum::String, PHP_INT_MAX),
                'DATE' => new Type(TypeEnum::DateTime, 0),
                'TIMESTAMP', 'TIMESTAMPTZ', 'TIMESTAMP WITHOUT TIME ZONE', 'TIMESTAMP WITH TIME ZONE' => new Type(TypeEnum::DateTime, 0),
                'TIME', 'TIMETZ', 'TIME WITHOUT TIME ZONE', 'TIME WITH TIME ZONE' => new Type(TypeEnum::DateTime, 0),
                'INTERVAL' => new Type(TypeEnum::DateTime, 0),
                'INET' => new Type(TypeEnum::String, PHP_INT_MAX),
                'CIDR' => new Type(TypeEnum::String, PHP_INT_MAX),
                'MACADDR' => new Type(TypeEnum::String, PHP_INT_MAX),
                'MONEY' => new Type(TypeEnum::Float, 64),
                'BIT' => new Type(TypeEnum::Int, 32),
                'VARBIT' => new Type(TypeEnum::Int, PHP_INT_MAX),
                'REGPROC' => new Type(TypeEnum::Int, 32),
                'REGTYPE' => new Type(TypeEnum::Int, 32),
                'REGROLE' => new Type(TypeEnum::Int, 32),
                'REGNAMESPACE' => new Type(TypeEnum::Int, 32),
                default => $type,
            };
        };

        $columns = $this->database->select(['information_schema', 'columns'])
            ->columns(['column_name', 'data_type', 'character_maximum_length', 'numeric_precision', 'is_nullable', 'column_default', 'is_identity', 'identity_generation'])
            ->whereEquals('table_schema', Query::raw('current_schema()'))
            ->whereEquals('table_name', is_array($table) ? end($table) : $table)
            ->orderByAsc('ordinal_position')
            ->execute()
            ->fetchAssocs();

        return array_map(
            fn(array $column): Column => new Column(
                $column['column_name'],
                $type(
                    $column['data_type'],
                    match (strtoupper($column['data_type'])) {
                        'CHARACTER VARYING', 'VARCHAR', 'CHARACTER', 'CHAR', 'BPCHAR' => $column['character_maximum_length'],
                        'NUMERIC', 'DECIMAL' => $column['numeric_precision'],
                        default => null,
                    }
                ),
                $column['is_nullable'] === 'YES',
                $this->extractDefault($column['column_default'], $column['is_identity']),
                $column['is_identity'] === 'YES'
            ),
            $columns
        );
    }

    public function primaryKeys(string|array|Sql $table): array
    {
        $primaryKeys = $this->database->select(['information_schema', 'key_column_usage'])
            ->columns(['column_name'])
            ->whereEquals('table_schema', Query::raw('current_schema()'))
            ->whereEquals('table_name', is_array($table) ? end($table) : $table)
            ->whereIn('constraint_name',
                $this->database->select(['information_schema', 'table_constraints'])
                    ->columns(['constraint_name'])
                    ->whereEquals('table_schema', Query::raw('current_schema()'))
                    ->whereEquals('table_name', is_array($table) ? end($table) : $table)
                    ->whereEquals('constraint_type', 'PRIMARY KEY')
            )
            ->orderByAsc('ordinal_position')
            ->execute()
            ->fetchAssocs();

        return array_column($primaryKeys, 'column_name');
    }

    public function uniqueConstraints(string|array|Sql $table): array
    {
        $indexes = $this->database->select(['information_schema', 'key_column_usage'])
            ->columns(['constraint_name', 'column_name'])
            ->whereEquals('table_schema', Query::raw('current_schema()'))
            ->whereEquals('table_name', is_array($table) ? end($table) : $table)
            ->whereIn('constraint_name',
                $this->database->select(['information_schema', 'table_constraints'])
                    ->columns(['constraint_name'])
                    ->whereEquals('table_schema', Query::raw('current_schema()'))
                    ->whereEquals('table_name', is_array($table) ? end($table) : $table)
                    ->whereEquals('constraint_type', 'UNIQUE')
            )
            ->orderByAsc('constraint_name')
            ->orderByAsc('ordinal_position')
            ->execute()
            ->fetchAssocs();

        $columns = [];

        foreach ($indexes as $index) {
            $columns[$index['constraint_name']][] = $index['column_name'];
        }

        return array_values(
            array_map(
                fn(string $name, array $columns) => new UniqueConstraint($columns, $name),
                array_keys($columns),
                array_values($columns)
            )
        );
    }

    public function foreignKeyConstraints(string|array|Sql $table): array
    {
        $constraints = $this->database->select(['information_schema', 'key_column_usage'])
            ->columns(['column_name', 'referenced_table_name', 'referenced_column_name', 'constraint_name'])
            ->whereEquals('table_schema', Query::raw('current_schema()'))
            ->whereEquals('table_name', is_array($table) ? end($table) : $table)
            ->whereIsNotNull('referenced_table_name')
            ->execute()
            ->fetchAssocs();

        $rules = $this->database->select(['information_schema', 'referential_constraints'])
            ->columns(['constraint_name', 'update_rule', 'delete_rule'])
            ->whereEquals('constraint_schema', Query::raw('current_schema()'))
            ->whereEquals('table_name', is_array($table) ? end($table) : $table)
            ->whereIn('constraint_name', array_unique(array_column($constraints, 'constraint_name')))
            ->execute()
            ->fetchAssocs();

        $constraintRules = [];

        foreach ($rules as $rule) {
            $constraintRules[$rule['constraint_name']] = [
                ReferentialActionEnum::tryFrom(strtoupper($rule['update_rule'])) ?? $rule['update_rule'],
                ReferentialActionEnum::tryFrom(strtoupper($rule['delete_rule'])) ?? $rule['delete_rule'],
            ];
        }

        return array_map(
            fn(array $constraint) => new ForeignKeyConstraint(
                $constraint['column_name'],
                $constraint['referenced_table_name'],
                $constraint['referenced_column_name'],
                $constraint['constraint_name'],
                $constraintRules[$constraint['constraint_name']][0],
                $constraintRules[$constraint['constraint_name']][1]
            ),
            $constraints
        );
    }

    public function indexes(string|array|Sql $table): array
    {
        $indexes = $this->database->select(Query::alias(['pg_catalog', 'pg_class'], 't'))
            ->columns([
                Query::raw('i.relname AS index_name'),
                Query::raw('a.attname AS column_name'),
                Query::raw('CASE WHEN ix.indisunique THEN 0 ELSE 1 END AS non_unique'),
            ])
            ->innerJoin(['pg_catalog', 'pg_index'], function (Join $join) {
                return $join->on(['t', 'oid'], ['ix', 'indrelid']);
            })
            ->innerJoin(Query::alias(['pg_catalog', 'pg_class'], 'i'), function (Join $join) {
                return $join->on(['ix', 'indexrelid'], ['i', 'oid']);
            })
            ->innerJoin(['pg_catalog', 'pg_namespace'], function (Join $join) {
                return $join->on(['t', 'relnamespace'], ['n', 'oid']);
            })
            ->join('INNER JOIN pg_catalog.pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)')
            ->leftJoin(['pg_catalog', 'pg_constraint'], function (Join $join) {
                return $join
                    ->on(['c', 'conindid'], ['i', 'oid'])
                    ->whereEquals(['c', 'contype'], 'f');
            })
            ->whereEquals(['n', 'nspname'], Query::raw('current_schema()'))
            ->whereEquals(['t', 'relname'], is_array($table) ? end($table) : $table)
            ->whereEquals(['ix', 'indisprimary'], Query::raw('false'))
            ->whereIsNull(['c', 'oid'])
            ->orderByAsc(Query::raw('i.relname'))
            ->orderByAsc(Query::raw('a.attnum'))
            ->execute()
            ->fetchAssocs();

        $indexColumns = [];

        foreach ($indexes as $index) {
            $indexColumns[$index['index_name']][] = $index['column_name'];
        }

        return array_map(
            fn(array $index): Index => new Index(
                $index['index_name'],
                $indexColumns[$index['index_name']],
                (bool) !$index['non_unique']
            ),
            $indexes
        );
    }

    /**
     * Extract the default value for a column, stripping nextval() for identity columns.
     */
    private function extractDefault(?string $default, string $isIdentity): mixed
    {
        if ($isIdentity === 'YES') {
            return true;
        }

        if ($default === null) {
            return null;
        }

        // Strip nextval('...'::regclass) for serial/auto-increment columns
        if (preg_match("/^nextval\('([^']+)'\s*::regclass\)$/", $default, $match)) {
            return true;
        }

        // Strip quotes from literal defaults (e.g., 'value'::text -> 'value')
        if (preg_match("/^'([^']*)'(?:::\w+)?$/", $default, $match)) {
            return $match[1];
        }

        // Handle numeric defaults and other expressions
        return $default;
    }
}