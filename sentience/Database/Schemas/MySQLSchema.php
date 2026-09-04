<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Type;

class MySQLSchema extends SQLSchema
{
    public function indexes(string $table): array
    {
        $indexes = $this->database->select(['information_schema', 'statistics'])
            ->columns(['INDEX_NAME', 'COLUMN_NAME', 'NON_UNIQUE'])
            ->whereEquals('TABLE_NAME', $table)
            ->whereNotContains('INDEX_NAME', 'PRIMARY', true)
            ->whereIn(
                'INDEX_NAME',
                $this->database->select(['information_schema', 'key_column_usage'])
                    ->columns(['CONSTRAINT_NAME'])
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
            fn (array $index): Index => new Index(
                $index['INDEX_NAME'],
                $indexColumns[$index['INDEX_NAME']],
                (bool) !$index['NON_UNIQUE']
            ),
            $indexes
        );
    }

    protected function type(string $type): string|Type
    {
        preg_match('/^(\w+)(?:\((\d+)\))?$/', $type, $match);

        $size = !empty($match[2]) ? $match[2] : null;

        return match ($match[1] ?? $match[0] ?? $type) {
            'TINYINT' => new Type(TypeEnum::Bool),
            'DOUBLE' => new Type(TypeEnum::Float, 64),
            'TEXT' => new Type(TypeEnum::String, $size ?? 65535),
            'MEDIUMTEXT' => new Type(TypeEnum::String, $size ?? 16777215),
            'LONGTEXT' => new Type(TypeEnum::String, $size ?? 4294967295),
            'DATETIME' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => parent::type($type)
        };
    }
}
