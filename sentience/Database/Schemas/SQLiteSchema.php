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

class SQLiteSchema extends SchemaAbstract
{
    public function tables(): array
    {
        $tables = $this->database->select('sqlite_master')
            ->columns(['name'])
            ->whereEquals('type', 'table')
            ->execute()
            ->fetchAssocs();

        return array_map(
            fn(array $row) => $row['name'],
            $tables
        );
    }

    public function columns(string|array|Sql $table): array
    {
        $type = function (string $type): string|Type {
            if ((bool) preg_match('/^(DECIMAL|VARCHAR)\((\d+)\)$/', $type, $matches)) {
                $size = (int) $matches[2];

                return match ($matches[1]) {
                    'DECIMAL' => new Type(TypeEnum::Float, $size),
                    'VARCHAR' => new Type(TypeEnum::String, $size),
                };
            }

            $types = [
                'BOOLEAN' => new Type(TypeEnum::Bool),
                'INTEGER' => new Type(TypeEnum::Int, 32),
                'BIGINT' => new Type(TypeEnum::Int, 64),
                'REAL' => new Type(TypeEnum::Float, 64),
                'VARCHAR',
                'TEXT' => new Type(TypeEnum::String, PHP_INT_MAX),
                'DATETIME' => new Type(TypeEnum::DateTime, 0),
            ];

            return $types[$type] ?? $type;
        };

        $columns = $this->database->query("PRAGMA table_info({$this->dialect->escapeIdentifier($table)})")->fetchAssocs();

        return array_map(
            fn(array $column): Column => new Column(
                $column['name'],
                $type($column['type']),
                (bool) $column['notnull'],
                $column['dflt_value'],
                (bool) $column['pk'] && $column['type'] == 'INTEGER'
            ),
            $columns
        );
    }

    public function primaryKeys(string|array|Sql $table): array
    {
        $rows = $this->database->query("PRAGMA table_info({$this->dialect->escapeIdentifier($table)})")->fetchAssocs();

        $columns = [];

        foreach ($rows as $row) {
            if ((int) $row['pk'] == 0) {
                continue;
            }

            $column = $row['name'];

            if (in_array($column, $columns)) {
                continue;
            }

            $columns[] = $column;
        }

        return $columns;
    }

    public function uniqueConstraints(string|array|Sql $table): array
    {
        $uniqueIndexes = array_filter(
            $this->indexes($table),
            fn(Index $index) => $index->unique
        );

        return array_values(
            array_map(
                fn(Index $index) => new UniqueConstraint($index->columns, $index->name),
                $uniqueIndexes
            )
        );
    }

    public function foreignKeyConstraints(string|array|Sql $table): array
    {
        $foreignKeys = $this->database->query("PRAGMA foreign_key_list({$this->dialect->escapeIdentifier($table)})")->fetchAssocs();

        return array_map(
            fn(array $foreignKey) => new ForeignKeyConstraint(
                $foreignKey['from'],
                $foreignKey['table'],
                $foreignKey['to'],
                null,
                ReferentialActionEnum::tryFrom($foreignKey['on_update']) ?: $foreignKey['on_update'],
                ReferentialActionEnum::tryFrom($foreignKey['on_delete']) ?: $foreignKey['on_delete']
            ),
            $foreignKeys
        );
    }

    public function indexes(string|array|Sql $table): array
    {
        $indexes = $this->database->query("PRAGMA index_list({$this->dialect->escapeIdentifier($table)})")->fetchAssocs();

        return array_map(
            function (array $index): Index {
                $name = $index['name'];
                $columns = array_column(
                    $this->database->query("PRAGMA index_info({$name})")
                        ->fetchAssocs(),
                    'name'
                );

                return new Index($name, $columns, (bool) $index['unique']);
            },
            $indexes
        );
    }
}
