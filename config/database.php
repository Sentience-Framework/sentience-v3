<?php

use Sentience\Helpers\Filesystem;

return [
    'driver' => env('DB_DRIVER', 'sqlite'),
    'mysql' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'sentience'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),

        /**
         * Highly expirimental!
         *
         * Asynchronous writes:
         * 'queries' => ['SET GLOBAL innodb_flush_log_at_trx_commit = 0;','SET GLOBAL sync_binlog = 0;']
         */
        'queries' => env('DB_QUERIES', []),

        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'engine' => env('DB_ENGINE', 'InnoDB'),
        'use_pdo' => env('DB_USE_PDO', false)
    ],
    'pgsql' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 5432),
        'name' => env('DB_NAME', 'sentience'),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),

        /**
         * Highly expirimental!
         *
         * Asynchronous writes:
         * 'queries' => ['SET synchronous_commit = OFF;']
         */
        'queries' => env('DB_QUERIES', []),

        'client_encoding' => env('DB_CLIENT_ENCODING', 'UTF8'),
        'search_path' => env('DB_SEARCH_PATH', 'public'),
        'use_pdo' => env('DB_USE_PDO', false)
    ],
    'sqlite' => [
        'file' => env('DB_FILE', Filesystem::path(SENTIENCE_DIR, 'sqlite', 'sentience.sqlite3')),

        /**
         * Highly expirimental!
         *
         * Asynchronous writes:
         * 'queries' => ['PRAGMA synchronous = OFF']
         */
        'queries' => env('DB_QUERIES', []),

        'read_only' => env('DB_READ_ONLY', false),
        'encryption_key' => env('DB_ENCRYPTION_KEY', ''),
        'busy_timeout' => env('DB_BUSY_TIMEOUT', 100),
        'encoding' => env('DB_ENCODING', 'UTF8'),
        'journal_mode' => env('DB_SQLITE_JOURNAL_MODE', 'WAL'),
        'foreign_keys' => env('DB_SQLITE_FOREIGN_KEYS', true),

        /**
         * The SQLite3 class contains a bug that executes queries multiple times.
         * For now it is recommended to use the PDO adapter.
         */
        'use_pdo' => env('DB_USE_PDO', true)
    ],
    'debug' => env('DB_DEBUG', false)
];
