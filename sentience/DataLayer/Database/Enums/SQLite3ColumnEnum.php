<?php

namespace Sentience\DataLayer\Database\Enums;

use DateTime;
use DateTimeImmutable;
use Sentience\Timestamp\Timestamp;

enum SQLite3ColumnEnum: string
{
    case BOOLEAN = 'BOOLEAN';
    case INTEGER = 'INTEGER';
    case BIGINT = 'BIGINT';
    case REAL = 'REAL';
    case DATE = 'DATE';
    case DATETIME = 'DATETIME';
    case TIME = 'TIME';
    case TIMESTAMP = 'TIMESTAMP';
    case VARCHAR = 'VARCHAR';
    case TEXT = 'TEXT';

    public static function getType(string $type): static
    {
        return match ($type) {
            'bool' => static::BOOLEAN,
            'int' => static::INTEGER,
            'float' => static::REAL,
            'string' => static::TEXT,
            DateTime::class,
            DateTimeImmutable::class,
            Timestamp::class => static::DATETIME,
            default => static::TEXT
        };
    }
}
