<?php

declare(strict_types=1);

namespace Modules\Database\Dialects;

use PDO;
use Modules\Database\Database;

class DialectFactory
{
    public const string PDO_DRIVER_MYSQL = 'mysql';
    public const string PDO_DRIVER_PGSQL = 'pgsql';
    public const PDO_DRIVER_SQLITE = 'sqlite';

    public static function fromDatabase(): DialectInterface
    {
        return static::fromPDODriver(Database::getInstance()->getPDOAttribute(PDO::ATTR_DRIVER_NAME));
    }

    public static function fromPDODriver(string $driver): DialectInterface
    {
        return match ($driver) {
            static::PDO_DRIVER_MYSQL => new Mysql(),
            static::PDO_DRIVER_PGSQL => new Pgsql(),
            static::PDO_DRIVER_SQLITE => new Sqlite(),
            default => new Sql()
        };
    }
}
