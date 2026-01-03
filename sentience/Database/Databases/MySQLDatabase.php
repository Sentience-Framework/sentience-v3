<?php

namespace Sentience\Database\Databases;

use Closure;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\SocketAbstract;

class MySQLDatabase extends DatabaseAbstract
{
    public function __construct(
        string $name,
        SocketAbstract $socket,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false,
        bool $lazy = false,
        null|int|string $version = null,
        bool $isMariaDB = false
    ) {
        $driver = $isMariaDB
            ? Driver::MARIADB
            : Driver::MYSQL;

        $adapter = $driver->getAdapter(
            $name,
            $socket,
            $queries,
            $options,
            $debug,
            $usePDOAdapter,
            $lazy
        );

        $version ??= $adapter->version();

        $dialect = $driver->getDialect($version);

        parent::__construct($adapter, $dialect);
    }
}
