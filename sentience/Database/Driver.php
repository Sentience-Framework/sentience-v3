<?php

namespace Sentience\Database;

use Sentience\Database\Adapters\MySQLiAdapter;
use Sentience\Database\Adapters\PDOAdapter;
use Sentience\Database\Adapters\SQLiteAdapter;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Dialects\MySQL;
use Sentience\Database\Dialects\PgSQL;
use Sentience\Database\Dialects\SQL;
use Sentience\Database\Dialects\SQLite;

enum Driver: string
{
    case MYSQL = 'mysql';
    case PGSQL = 'pgsql';
    case SQLITE = 'sqlite';

    public function getAdapter(): string
    {
        return match ($this) {
            // static::MYSQL => PDOAdapter::class,
            static::MYSQL => MySQLiAdapter::class,
            static::PGSQL => PDOAdapter::class,
            // static::SQLITE => PDOAdapter::class,
            static::SQLITE => SQLiteAdapter::class,
            default => PDOAdapter::class
        };
    }

    public function getDialect(): DialectInterface
    {
        return match ($this) {
            static::MYSQL => new MySQL(),
            static::PGSQL => new PgSQL(),
            static::SQLITE => new SQLite(),
            default => new SQL()
        };
    }
}
