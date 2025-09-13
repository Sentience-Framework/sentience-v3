<?php

namespace Modules\Database\Dialects;

use DateTimeInterface;
use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Objects\Raw;
use Modules\Timestamp\Timestamp;

interface DialectInterface
{
    public const string CONFIG_DISTINCT = 'distinct';
    public const string CONFIG_COLUMNS = 'columns';
    public const string CONFIG_TABLE = 'table';
    public const string CONFIG_JOINS = 'joins';
    public const string CONFIG_WHERE = 'where';
    public const string CONFIG_GROUP_BY = 'groupBy';
    public const string CONFIG_HAVING = 'having';
    public const string CONFIG_ORDER_BY = 'orderBy';
    public const string CONFIG_LIMIT = 'limit';
    public const string CONFIG_OFFSET = 'offset';
    public const string CONFIG_VALUES = 'values';
    public const string CONFIG_ON_CONFLICT = 'onConflict';
    public const string CONFIG_ON_CONFLICT_CONFLICT = 'conflict';
    public const string CONFIG_ON_CONFLICT_UPDATES = 'updates';
    public const string CONFIG_ON_CONFLICT_PRIMARY_KEY = 'primaryKey';
    public const string CONFIG_RETURNING = 'returning';
    public const string CONFIG_IF_NOT_EXISTS = 'ifNotExists';
    public const string CONFIG_PRIMARY_KEYS = 'primaryKeys';
    public const string CONFIG_UNIQUE_CONSTRAINTS = 'uniqueConstraints';
    public const string CONFIG_FOREIGN_KEY_CONSTRAINTS = 'foreignKeyConstraints';
    public const string CONFIG_ALTERS = 'alters';
    public const string CONFIG_IF_EXISTS = 'ifExists';

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
