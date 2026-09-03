<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\Query;

class MySQLSchema extends SchemaAbstract
{
    public function tables(): array
    {
        $tables = $this->database->select(['information_schema', 'tables'])
            ->columns(['TABLE_NAME'])
            ->whereEquals('TABLE_SCHEMA', Query::raw('database()'))
            ->whereEquals('TABLE_TYPE', 'BASE TABLE')
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'TABLE_NAME');
    }

    public function columns(string|array|Sql $table): array
    {
        $type = function (string $type): string|Type {
            preg_match('/^(\w+)(?:\((\d+)\))?$/', $type, $match);

            $size = !empty($match[2]) ? $match[2] : null;

            return match ($match[1]) {
                'TINYINT' => new Type(TypeEnum::Bool),
                'INTEGER' => new Type(TypeEnum::Int, 32),
                'BIGINT' => new Type(TypeEnum::Int, 64),
                'FLOAT' => new Type(TypeEnum::Float, 32),
                'DOUBLE' => new Type(TypeEnum::Float, 64),
                'VARCHAR' => new Type(TypeEnum::String, $size ?? 255),
                'TEXT' => new Type(TypeEnum::String, $size ?? 65535),
                'MEDIUMTEXT' => new Type(TypeEnum::String, $size ?? 16777215),
                'LONGTEXT' => new Type(TypeEnum::String, $size ?? 4294967295),
                'DATETIME' => new Type(TypeEnum::DateTime, $size ?? 0),
                default => $type,
            };
        };

        $columns = $this->database->select(['information_schema', 'columns'])
            ->columns(['COLUMN_NAME', 'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'COLUMN_KEY'])
            ->whereEquals('TABLE_SCHEMA', Query::raw('database()'))
            ->whereEquals('TABLE_NAME', is_array($table) ? end($table) : $table)
            ->orderByAsc('ORDINAL_POSITION')
            ->execute()
            ->fetchAssocs();

        return array_map(
            fn(array $column): Column => new Column(
                $column['COLUMN_NAME'],
                $type(strtoupper($column['COLUMN_TYPE'])),
                $column['IS_NULLABLE'] === 'YES',
                $column['COLUMN_DEFAULT'],
                $column['COLUMN_KEY'] === 'PRI' && in_array(strtoupper($column['COLUMN_TYPE']), ['INTEGER', 'BIGINT'])
            ),
            $columns
        );
    }

    public function primaryKeys(string|array|Sql $table): array
    {
        $primaryKeys = $this->database->select(['information_schema', 'statistics'])
            ->columns(['COLUMN_NAME'])
            ->whereEquals('TABLE_SCHEMA', Query::raw('database()'))
            ->whereEquals('TABLE_NAME', is_array($table) ? end($table) : $table)
            ->whereEquals('INDEX_NAME', 'PRIMARY')
            ->orderByAsc('SEQ_IN_INDEX')
            ->execute()
            ->fetchAssocs();

        return array_column($primaryKeys, 'COLUMN_NAME');
    }

    public function uniqueConstraints(string|array|Sql $table): array
    {
        $indexes = $this->database->select(['information_schema', 'statistics'])
            ->columns(['INDEX_NAME', 'COLUMN_NAME'])
            ->whereEquals('TABLE_SCHEMA', Query::raw('database()'))
            ->whereEquals('TABLE_NAME', is_array($table) ? end($table) : $table)
            ->whereNotEquals('INDEX_NAME', 'PRIMARY')
            ->whereEquals('NON_UNIQUE', 0)
            ->orderByAsc('INDEX_NAME')
            ->orderByAsc('SEQ_IN_INDEX')
            ->execute()
            ->fetchAssocs();

        $columns = [];

        foreach ($indexes as $index) {
            $columns[$index['INDEX_NAME']][] = $index['COLUMN_NAME'];
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
        $constaints = $this->database->select(['information_schema', 'key_column_usage'])
            ->columns(['COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME', 'CONSTRAINT_NAME'])
            ->whereEquals('TABLE_SCHEMA', Query::raw('database()'))
            ->whereEquals('TABLE_NAME', is_array($table) ? end($table) : $table)
            ->whereIsNotNull('REFERENCED_TABLE_NAME')
            ->execute()
            ->fetchAssocs();

        $rules = $this->database->select(['information_schema', 'referential_constraints'])
            ->columns(['CONSTRAINT_NAME', 'UPDATE_RULE', 'DELETE_RULE'])
            ->whereEquals('CONSTRAINT_SCHEMA', Query::raw('database()'))
            ->whereEquals('TABLE_NAME', is_array($table) ? end($table) : $table)
            ->whereIn('CONSTRAINT_NAME', array_unique(array_column($constaints, 'CONSTRAINT_NAME')))
            ->execute()
            ->fetchAssocs();

        $constraintRules = [];

        foreach ($rules as $rule) {
            $constraintRules[$rule['CONSTRAINT_NAME']] = [
                ReferentialActionEnum::tryFrom($rule['UPDATE_RULE']) ?? $rule['UPDATE_RULE'],
                ReferentialActionEnum::tryFrom($rule['DELETE_RULE']) ?? $rule['DELETE_RULE'],
            ];
        }

        return array_map(
            fn(array $constraint) => new ForeignKeyConstraint(
                $constraint['COLUMN_NAME'],
                $constraint['REFERENCED_TABLE_NAME'],
                $constraint['REFERENCED_COLUMN_NAME'],
                $constraint['CONSTRAINT_NAME'],
                $constraintRules[$constraint['CONSTRAINT_NAME']][0],
                $constraintRules[$constraint['CONSTRAINT_NAME']][1]
            ),
            $constaints
        );
    }

    public function indexes(string|array|Sql $table): array
    {
        $indexes = $this->database->select(['information_schema', 'statistics'])
            ->columns(['INDEX_NAME', 'COLUMN_NAME', 'NON_UNIQUE'])
            ->whereEquals('TABLE_SCHEMA', Query::raw('database()'))
            ->whereEquals('TABLE_NAME', is_array($table) ? end($table) : $table)
            ->whereNotEquals('INDEX_NAME', 'PRIMARY')
            ->whereIn(
                'INDEX_NAME',
                $this->database->select(['information_schema', 'key_column_usage'])
                    ->columns(['CONSTRAINT_NAME'])
                    ->whereEquals('TABLE_SCHEMA', Query::raw('database()'))
                    ->whereIsNull('REFERENCED_TABLE_NAME')
                    ->whereIsNull('REFERENCED_COLUMN_NAME')
            )
            ->execute()
            ->fetchAssocs();

        $indexColumns = [];

        foreach ($indexes as $index) {
            $indexColumns[$index['INDEX_NAME']][] = $index['COLUMN_NAME'];
        }

        return array_map(
            fn(array $index): Index => new Index(
                $index['INDEX_NAME'],
                $indexColumns[$index['INDEX_NAME']],
                (bool) !$index['NON_UNIQUE']
            ),
            $indexes
        );
    }
}
