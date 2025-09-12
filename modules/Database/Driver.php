<?php

namespace Modules\Database;

use Modules\Database\Adapters\PDOAdapter;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Dialects\Mysql;
use Modules\Database\Dialects\Pgsql;
use Modules\Database\Dialects\Sql;
use Modules\Database\Dialects\Sqlite;

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
            static::SQLITE => PDOAdapter::class,
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
