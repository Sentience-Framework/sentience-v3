<?php

declare(strict_types=1);

namespace Sentience\Database\Dialects;

use PDO;
use Sentience\Database\Database;

class DialectFactory
{
    public const PDO_DRIVER_MYSQL = 'mysql';
    public const PDO_DRIVER_PGSQL = 'pgsql';
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
