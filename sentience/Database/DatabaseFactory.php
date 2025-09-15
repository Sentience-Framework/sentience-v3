<?php

namespace Sentience\Database;

use Closure;

class DatabaseFactory
{
    public static function create(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        ?Closure $debug,
        array $options,
        bool $usePDOAdapter = false
    ): Database {
        $adapter = $driver->getAdapter(
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $debug,
            $options,
            $usePDOAdapter
        );

        $dialect = $driver->getDialect();

        return new Database($adapter, $dialect);
    }
}
