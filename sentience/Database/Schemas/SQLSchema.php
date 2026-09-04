<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\Query;

class SQLSchema extends SchemaAbstract
{
    public function tables(): array
    {
        $tables = $this->database->select(['information_schema', 'tables'])
            ->columns(['table_name' => 'table_name'])
            ->whereLike('table_type', 'BASE TABLE', true)
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'table_name');
    }

    public function columns(string $table): array
    {
        $columns = $this->database->select(['information_schema', 'columns'])
            ->columns([
                'column_name' => 'column_name',
                'is_nullable' => 'is_nullable',
                'column_default' => 'column_default',
                'data_type' => 'data_type',
                'character_maximum_length' => 'character_maximum_length',
                'numeric_precision' => 'numeric_precision',
                'datetime_precision' => 'datetime_precision',
                Query::raw('information_schema.columns.*')
            ])
            ->whereEquals('table_name', $table)
            ->orderByAsc('ordinal_position')
            ->execute()
            ->fetchAssocs();

        return array_map(
            function (array $column): Column {
                $type = $column['data_type'];
                $size = $column['character_maximum_length'] ?? $column['numeric_precision'] ?? $column['datetime_precision'];

                if (!is_null($size) && !str_contains($type, '(')) {
                    $type = sprintf('%s(%d)', $type, $size);
                }

                $identity = $this->true($column['is_identity'] ?? null) || (bool) preg_match('/.*increment.*/i', array_change_key_case($column, CASE_LOWER)['extra'] ?? '');

                return new Column(
                    $column['column_name'],
                    $this->type(strtoupper($type)),
                    $this->true($column['is_nullable']),
                    $column['column_default'],
                    $identity
                );
            },
            $columns
        );
    }

    public function primaryKeys(string $table): array
    {
        $primaryKeys = $this->database->select(['information_schema', 'key_column_usage'])
            ->columns(['column_name' => 'column_name'])
            ->whereEquals('table_name', $table)
            ->whereIn(
                'constraint_name',
                $this->database->select(['information_schema', 'table_constraints'])
                    ->columns(['constraint_name'])
                    ->whereEquals('table_name', $table)
                    ->whereContains('constraint_type', 'PRIMARY', true)
            )
            ->orderByAsc('ordinal_position')
            ->execute()
            ->fetchAssocs();

        return array_column($primaryKeys, 'column_name');
    }

    public function uniqueConstraints(string $table): array
    {
        $indexes = $this->database->select(['information_schema', 'key_column_usage'])
            ->columns([
                'constraint_name' => 'constraint_name',
                'column_name' => 'column_name'
            ])
            ->whereEquals('table_name', $table)
            ->whereIn(
                'constraint_name',
                $this->database->select(['information_schema', 'table_constraints'])
                    ->columns(['constraint_name'])
                    ->whereEquals('table_name', $table)
                    ->whereContains('constraint_type', 'UNIQUE', true)
            )
            ->orderByAsc('constraint_name')
            ->orderByAsc('ordinal_position')
            ->execute()
            ->fetchAssocs();

        $constraints = [];

        foreach ($indexes as $index) {
            $constraints[$index['constraint_name']][] = $index['column_name'];
        }

        $uniqueConstraints = [];

        foreach ($constraints as $name => $columns) {
            $uniqueConstraints[] = new UniqueConstraint($columns, $name);
        }

        return $uniqueConstraints;
    }

    public function foreignKeyConstraints(string $table): array
    {
        $constraints = array_map(
            fn (array $constraint) => array_change_key_case($constraint, CASE_LOWER),
            $this->database->select(['information_schema', 'referential_constraints'])
                ->columns([
                    'constraint_name' => 'constraint_name',
                    'unique_constraint_name' => 'unique_constraint_name',
                    'update_rule' => 'update_rule',
                    'delete_rule' => 'delete_rule',
                    Query::raw('information_schema.referential_constraints.*')
                ])
                ->whereIn(
                    'constraint_name',
                    $this->database->select(['information_schema', 'table_constraints'])
                        ->columns(['constraint_name'])
                        ->whereEquals('table_name', $table)
                        ->whereContains('constraint_type', 'FOREIGN', true)
                )
                ->execute()
                ->fetchAssocs()
        );

        $columns = $this->database->select(['information_schema', 'key_column_usage'])
            ->columns([
                'constraint_name' => 'constraint_name',
                'table_name' => 'table_name',
                'column_name' => 'column_name'
            ])
            ->whereIn(
                'constraint_name',
                [
                    ...array_column($constraints, 'constraint_name'),
                    ...array_column($constraints, 'unique_constraint_name')
                ]
            )
            ->orderByAsc('ordinal_position')
            ->execute()
            ->fetchAssocs();

        $constraintColumns = [];

        foreach ($columns as $column) {
            $constraintName = $column['constraint_name'];
            $tableName = $column['table_name'];
            $columnName = $column['column_name'];

            $constraintColumns[$constraintName][$tableName][] = $columnName;
        }

        $foreignKeyConstraints = [];

        foreach ($constraints as $constraint) {
            $constraintName = $constraint['constraint_name'];
            $references = $constraintColumns[$constraint['unique_constraint_name']] ?? [];
            $referenceTable = array_key_first($references);
            $updateRule = $constraint['update_rule'];
            $deleteRule = $constraint['delete_rule'];

            foreach ($constraintColumns[$constraintName][$table] ?? [] as $index => $column) {
                $foreignKeyConstraints[] = new ForeignKeyConstraint(
                    $column,
                    $referenceTable,
                    $references[$referenceTable][$index],
                    $constraintName,
                    ReferentialActionEnum::tryFrom(strtoupper($updateRule)) ?? $updateRule,
                    ReferentialActionEnum::tryFrom(strtoupper($deleteRule)) ?? $deleteRule
                );
            }
        }

        return $foreignKeyConstraints;
    }

    public function indexes(string $table): array
    {
        $uniqueConstraints = $this->uniqueConstraints($table);
        $foreignKeyConstraints = $this->foreignKeyConstraints($table);

        $indexes = [];

        foreach ($uniqueConstraints as $uniqueConstraint) {
            $indexes[] = new Index($uniqueConstraint->name, $uniqueConstraint->columns, true);
        }

        foreach ($foreignKeyConstraints as $foreignKeyConstraint) {
            $indexes[] = new Index($foreignKeyConstraint->name, [$foreignKeyConstraint->column], false);
        }

        return $indexes;
    }

    protected function type(string $type): string|Type
    {
        preg_match('/^(\w+)(?:\((\d+)\))?$/', $type, $match);

        $size = !empty($match[2]) ? $match[2] : null;

        return match ($match[1] ?? $type) {
            'BOOLEAN',
            'BOOL' => new Type(TypeEnum::Bool),
            'INTEGER',
            'INT' => new Type(TypeEnum::Int, 32),
            'BIGINT' => new Type(TypeEnum::Int, 64),
            'REAL',
            'FLOAT',
            'DOUBLE',
            'DECIMAL' => new Type(TypeEnum::Float, 64),
            'VARCHAR',
            'TEXT' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            'DATETIME',
            'TIMESTAMP' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => $type
        };
    }

    protected function true(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        return in_array(
            strtolower((string) $value),
            [
                'yes',
                'true',
                '1'
            ]
        );
    }
}
