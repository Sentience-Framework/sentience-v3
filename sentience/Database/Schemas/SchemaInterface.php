<?php

namespace Sentience\Database\Schemas;

interface SchemaInterface
{
    public function tables(): array;
    public function columns(string $table): array;
    public function primaryKeys(string $table): array;
    public function uniqueConstraints(string $table): array;
    public function foreignKeyConstraints(string $table): array;
    public function indexes(string $table): array;
}
