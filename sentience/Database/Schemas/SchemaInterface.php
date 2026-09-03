<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Interfaces\Sql;

interface SchemaInterface
{
    public function tables(): array;
    public function columns(string|array|Sql $table): array;
    public function primaryKeys(string|array|Sql $table): array;
    public function uniqueConstraints(string|array|Sql $table): array;
    public function foreignKeyConstraints(string|array|Sql $table): array;
    public function indexes(string|array|Sql $table): array;
}
