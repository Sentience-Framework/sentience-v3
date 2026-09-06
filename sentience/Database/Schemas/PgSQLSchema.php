<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\Query;

class PgSQLSchema extends SQLSchema
{
    public function indexes(string $table): array
    {
        $rows = $this->database->select(Query::alias(['pg_catalog', 'pg_index'], 'ix'))
            ->columns([
                'index_name' => ['i', 'relname'],
                'column_name' => ['a', 'attname'],
                'unique' => ['ix', 'indisunique']
            ])
            ->innerJoin(
                Query::alias(['pg_catalog', 'pg_class'], 't'),
                fn (Join $join): Join => $join->on(['t', 'oid'], ['ix', 'indrelid'])
            )
            ->innerJoin(
                Query::alias(['pg_catalog', 'pg_class'], 'i'),
                fn (Join $join): Join => $join->on(['i', 'oid'], ['ix', 'indexrelid'])
            )
            ->innerJoin(
                Query::alias(['pg_catalog', 'pg_attribute'], 'a'),
                fn (Join $join): Join => $join
                    ->on(['a', 'attrelid'], ['t', 'oid'])
                    ->whereEquals(['a', 'attnum'], Query::raw('any(ix.indkey)'))
            )
            ->whereEquals(['t', 'relname'], $table)
            ->whereEquals(['ix', 'indisprimary'], Query::raw('false'))
            ->orderByAsc(['i', 'relname'])
            ->orderByAsc(Query::raw('array_position(ix.indkey::int2[], a.attnum)'))
            ->execute()
            ->fetchAssocs();

        $indexes = [];

        foreach ($rows as $row) {
            $indexes[$row['index_name']]['unique'] = (bool) $row['unique'];
            $indexes[$row['index_name']]['columns'][] = $row['column_name'];
        }

        return array_map(
            fn (string $name, array $index): Index => new Index($name, $index['columns'], $index['unique']),
            array_keys($indexes),
            array_values($indexes)
        );
    }

    protected function databaseSchema(WhereGroup $whereGroup): WhereGroup
    {
        return $whereGroup->whereEquals('table_schema', Query::raw('current_schema()'));
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return match ($match[1] ?? $type) {
            'DOUBLE PRECISION' => new Type(TypeEnum::Float, 64),
            'CHARACTER VARYING' => new Type(TypeEnum::String, $size ?? 255),
            'TIMESTAMP WITHOUT TIME ZONE',
            'TIMESTAMP WITH TIME ZONE' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => parent::type($type, $size)
        };
    }
}
