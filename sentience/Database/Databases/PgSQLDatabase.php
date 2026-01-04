<?php

namespace Sentience\Database\Databases;

use Closure;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;
use Sentience\Database\Sockets\UnixSocket;

class PgSQLDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::MYSQL;

    public function fromHost(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 5432,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->getAdapter(
            $name,
            new NetworkSocket($host, $port, $username, $password),
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public function fromUnixSocket(
        string $name,
        string $username,
        ?string $password,
        string $unixSocket,
        int $port,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->getAdapter(
            $name,
            new UnixSocket($unixSocket, $port, $username, $password),
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }
}
