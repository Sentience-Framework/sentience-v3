<?php

namespace src\database\dialects;

use PDO;
use src\database\Database;

class DialectFactory
{
    public const PDO_DRIVER_MYSQL = 'mysql';
    public const PDO_DRIVER_PGSQL = 'pgsql';
    public const PDO_DRIVER_SQLITE = 'sqlite';

    public static function fromDatabase(Database $database): DialectInterface
    {
        return static::fromDriver(
            $database->getPDOAttribute(PDO::ATTR_DRIVER_NAME),
            $database->getPDOAttribute(PDO::ATTR_SERVER_VERSION)
        );
    }

    public static function fromDriver(string $driver, string $version): DialectInterface
    {
        return match ($driver) {
            static::PDO_DRIVER_MYSQL => new Mysql($version),
            static::PDO_DRIVER_PGSQL => new Pgsql($version),
            static::PDO_DRIVER_SQLITE => new Sqlite($version),
            default => new Sql('')
        };
    }
}
