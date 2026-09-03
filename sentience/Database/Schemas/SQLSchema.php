<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Query;

class SQLSchema extends SchemaAbstract
{
    public function tables(): array
    {
        $tables = $this->database->select(['information_schema', 'tables'])
            ->columns(['table_name'])
            ->whereEquals('table_type', 'BASE TABLE')
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'table_name');
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

    protected function type(string $type, ?int $size = null): string|Type
    {
        preg_match('/^(\w+)(?:\((\d+)\))?$/', $type, $match);

        $size ??= (!empty($match[2]) ? $match[2] : null);

        return match ($match[1]) {
            'BOOLEAN' => new Type(TypeEnum::Bool),
            'INTEGER' => new Type(TypeEnum::Int, 32),
            'BIGINT' => new Type(TypeEnum::Int, 64),
            'REAL',
            'FLOAT',
            'DECIMAL' => new Type(TypeEnum::Float, 64),
            'VARCHAR',
            'TEXT' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            'DATETIME' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => $type
        };
    }
}
