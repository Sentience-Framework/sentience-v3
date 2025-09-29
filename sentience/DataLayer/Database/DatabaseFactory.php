<?php

namespace Sentience\DataLayer\Database;

use Closure;
use Sentience\Database\Adapters\AdapterAbstract;
use Sentience\Database\Driver;

class DatabaseFactory
{
    public static function createMySQL(
        string $name,
        string $host = 'localhost',
        int $port = 3306,
        string $username = 'root',
        string $password = '',
        string $charset = 'utf8mb4',
        string $engine = 'InnoDB',
        array $queries = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): Database {
        return new Database(
            Driver::MYSQL,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            [
                AdapterAbstract::OPTIONS_MYSQL_CHARSET => $charset,
                AdapterAbstract::OPTIONS_MYSQL_ENGINE => $engine
            ],
            $debug,
            $usePDOAdapter
        );
    }

    public static function createPgSQL(
        string $name,
        string $host = 'localhost',
        int $port = 5432,
        string $username = 'postgres',
        string $password = '',
        string $clientEncoding = 'UTF8',
        array $queries = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): Database {
        return new Database(
            Driver::PGSQL,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            [
                AdapterAbstract::OPTIONS_PGSQL_CLIENT_ENCODING => $clientEncoding
            ],
            $debug,
            $usePDOAdapter
        );
    }

    public static function createSQLite(
        string $file,
        string $encoding = 'UTF8',
        string $journalMode = 'WAL',
        string $foreignKeys = true,
        string $readOnly = false,
        string $encryptionKey = '',
        int $busyTimeout = 100,
        array $queries = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = true
    ): Database {
        return new Database(
            Driver::SQLITE,
            '',
            0,
            $file,
            '',
            '',
            $queries,
            [
                AdapterAbstract::OPTIONS_SQLITE_ENCODING => $encoding,
                AdapterAbstract::OPTIONS_SQLITE_JOURNAL_MODE => $journalMode,
                AdapterAbstract::OPTIONS_SQLITE_FOREIGN_KEYS => $foreignKeys,
                AdapterAbstract::OPTIONS_SQLITE_READ_ONLY => $readOnly,
                AdapterAbstract::OPTIONS_SQLITE_ENCRYPTION_KEY => $encryptionKey,
                AdapterAbstract::OPTIONS_SQLITE_BUSY_TIMEOUT => $busyTimeout
            ],
            $debug,
            $usePDOAdapter
        );
    }
}
