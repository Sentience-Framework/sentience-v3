<?php

declare(strict_types=1);

namespace Modules\Database\Dialects;

use DateTimeInterface;
use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Objects\Raw;
use Modules\Timestamp\Timestamp;

interface DialectInterface
{
    public function select(array $config): QueryWithParams;
    public function insert(array $config): QueryWithParams;
    public function update(array $config): QueryWithParams;
    public function delete(array $config): QueryWithParams;
    public function createTable(array $config): QueryWithParams;
    public function alterTable(array $config): array;
    public function dropTable(array $config): QueryWithParams;
    public function escapeIdentifier(string|array|Raw $identifier): string;
    public function escapeString(string $string): string;
    public function castToDriver(mixed $value): mixed;
    public function castToQuery(mixed $value): mixed;
    public function castBool(bool $bool): mixed;
    public function castTimestamp(DateTimeInterface $timestamp): mixed;
    public function parseBool(mixed $bool): bool;
    public function parseTimestamp(string $string): ?Timestamp;
    public function phpTypeToColumnType(string $type, bool $autoIncrement, bool $isPrimaryKey, bool $inConstraint): string;
}
