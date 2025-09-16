<?php

namespace Sentience\Database\Dialects;

use DateTimeInterface;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Objects\RawObject;
use Sentience\Timestamp\Timestamp;

interface DialectInterface
{
    public function select(array $config): QueryWithParamsObject;
    public function insert(array $config): QueryWithParamsObject;
    public function update(array $config): QueryWithParamsObject;
    public function delete(array $config): QueryWithParamsObject;
    public function createTable(array $config): QueryWithParamsObject;
    public function alterTable(array $config): array;
    public function dropTable(array $config): QueryWithParamsObject;
    public function escapeIdentifier(string|array|RawObject $identifier): string;
    public function escapeString(string $string): string;
    public function castToDriver(mixed $value): mixed;
    public function castToQuery(mixed $value): mixed;
    public function castBool(bool $bool): mixed;
    public function castTimestamp(DateTimeInterface $timestamp): mixed;
    public function parseBool(mixed $bool): bool;
    public function parseTimestamp(string $string): ?Timestamp;
    public function phpTypeToColumnType(string $type, bool $autoIncrement, bool $isPrimaryKey, bool $inConstraint): string;
}
