<?php

namespace Sentience\Database;

use Sentience\Database\Adapters\PDOAdapter;
use Sentience\Database\Adapters\SQLiteAdapter;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Dialects\Mysql;
use Sentience\Database\Dialects\Pgsql;
use Sentience\Database\Dialects\Sql;
use Sentience\Database\Dialects\Sqlite;

enum Driver: string
{
    case MYSQL = 'mysql';
    case PGSQL = 'pgsql';
    case SQLITE = 'sqlite';

    public function getAdapter(): string
    {
        return match ($this) {
            static::MYSQL => PDOAdapter::class,
            static::PGSQL => PDOAdapter::class,
            // static::SQLITE => PDOAdapter::class,
            static::SQLITE => SQLiteAdapter::class,
            default => PDOAdapter::class
        };
    }

    public function getDialect(): DialectInterface
    {
        return match ($this) {
            static::MYSQL => new Mysql(),
            static::PGSQL => new Pgsql(),
            static::SQLITE => new Sqlite(),
            default => new Sql()
        };
    }
}
