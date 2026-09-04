<?php

namespace Sentience\Database;

use Closure;
use PDO;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Schemas\SchemaInterface;
use Sentience\Database\Sockets\SocketAbstract;

class Database extends DatabaseAbstract
{
    public function __construct(AdapterInterface $adapter, DialectInterface $dialect, protected DriverInterface $driver)
    {
        parent::__construct($adapter, $dialect);
    }

    public static function connect(
        DriverInterface $driver,
        string $name,
        ?SocketAbstract $socket = null,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $adapter = $driver->adapter(
            $name,
            $socket,
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->dialect($version, $options);

        return new static($adapter, $dialect, $driver);
    }

    public static function drivers(): array
    {
        $drivers = [];

        if (class_exists('PDO')) {
            foreach (PDO::getAvailableDrivers() as $pdoDriver) {
                $driver = Driver::tryFrom($pdoDriver);

                if (!$driver) {
                    continue;
                }

                $drivers[] = $driver;
            }

            if (in_array(Driver::MySQL, $drivers)) {
                $drivers[] = Driver::MariaDB;
            }
        }

        if (class_exists('mysqli')) {
            foreach ([Driver::MariaDB, Driver::MySQL] as $driver) {
                if (in_array($driver, $drivers)) {
                    continue;
                }

                $drivers[] = $driver;
            }
        }

        if (class_exists('SQLite3')) {
            if (!in_array(Driver::SQLite, $drivers)) {
                $drivers[] = Driver::SQLite;
            }
        }

        return $drivers;
    }

    public function schema(): SchemaInterface
    {
        return $this->driver->schema($this, $this->dialect);
    }
}
