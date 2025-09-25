<?php

namespace Sentience\DataLayer\Database\Enums;

use DateTime;
use DateTimeImmutable;
use Sentience\Timestamp\Timestamp;

enum PgSQLColumnEnum: string
{
    case BOOLEAN = 'BOOLEAN';
    case SMALLINT = 'INT2';
    case INT = 'INT4';
    case BIGINT = 'INT8';
    case FLOAT = 'FLOAT8';
    case SERIAL = 'SERIAL';
    case BIGSERIAL = 'BIGSERIAL';
    case DATE = 'DATE';
    case TIME = 'TIME';
    case TIME_WITH_TIMEZONE = 'TIMETZ';
    case TIMESTAMP = 'TIMESTAMP';
    case TIMESTAMP_WITH_TIMEZONE = 'TIMESTAMPTZ';
    case VARCHAR = 'VARCHAR';
    case TEXT = 'TEXT';

    public static function getType(string $type, bool $isAutoIncrement): static
    {
        if ($type == 'int') {
            if ($isAutoIncrement) {
                return static::BIGSERIAL;
            }
        }

        return match ($type) {
            'bool' => static::BOOLEAN,
            'int' => static::BIGINT,
            'float' => static::FLOAT,
            'string' => static::TEXT,
            DateTime::class,
            DateTimeImmutable::class,
            Timestamp::class => static::TIMESTAMP,
            default => static::TEXT
        };
    }
}
