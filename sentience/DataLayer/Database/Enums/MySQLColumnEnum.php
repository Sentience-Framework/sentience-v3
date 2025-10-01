<?php

namespace Sentience\DataLayer\Database\Enums;

use DateTime;
use DateTimeImmutable;
use Sentience\Timestamp\Timestamp;

enum MySQLColumnEnum: string
{
    public const string AUTO_INCREMENT = 'AUTO_INCREMENT';

    case TINYINT = 'TINYINT';
    case SMALLINT = 'SMALLINT';
    case MEDIUMINT = 'MEDIUMINT';
    case INT = 'INT';
    case BIGINT = 'BIGINT';
    case FLOAT = 'FLOAT';
    case DATE = 'DATE';
    case TIME = 'TIME';
    case DATETIME = 'DATETIME';
    case DATETIME_WITH_MICROSECONDS = 'DATETIME(6)';
    case SMALL_VARCHAR = 'VARCHAR(64)';
    case MEDUIM_VARCHAR = 'VARCHAR(128)';
    case LONG_VARCHAR = 'VARCHAR(255)';
    case TINYTEXT = 'TINYTEXT';
    case TEXT = 'TEXT';
    case MEDIUMTEXT = 'MEDIUMTEXT';
    case LONGTEXT = 'LONGTEXT';

    public static function getType(string $type, bool $isPrimaryKey, bool $inConstraint): static
    {
        if ($type == 'int') {
            if ($isPrimaryKey) {
                return static::INT;
            }
        }

        if ($type == 'string') {
            if ($isPrimaryKey) {
                return static::SMALL_VARCHAR;
            }

            if ($inConstraint) {
                return static::MEDUIM_VARCHAR;
            }
        }

        return match ($type) {
            'bool' => static::TINYINT,
            'int' => static::BIGINT,
            'float' => static::FLOAT,
            'string' => static::LONGTEXT,
            DateTime::class,
            DateTimeImmutable::class,
            Timestamp::class => static::DATETIME_WITH_MICROSECONDS,
            default => static::LONG_VARCHAR
        };
    }
}
