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
        'queries' => env('DB_QUERIES', []),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'use_pdo' => env('DB_USE_PDO', false)
    ],
    'pgsql' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 5432),
        'name' => env('DB_NAME', 'sentience'),
        'username' => env('DB_USERNAME', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
        'queries' => env('DB_QUERIES', []),
        'use_pdo' => env('DB_USE_PDO', false)
    ],
    'sqlite' => [
        'file' => env('DB_FILE', Filesystem::path(SENTIENCE_DIR, 'sqlite', 'sentience.sqlite3')),
        'queries' => env('DB_QUERIES', ['PRAGMA journal_mode=WAL;']),
        'journal_mode' => env('DB_SQLITE_JOURNAL_MODE', 'WAL'),
        'foreign_keys' => env('DB_SQLITE_FOREIGN_KEYS', true),

        /**
         * The SQLite3 class contains a bug that executes queries multiple times.
         * For now it is recommended to use the PDO adapter.
         */
        'use_pdo' => env('DB_USE_PDO', true),

        /**
         * These options only work when use_pdo is disabled.
         */
        'sqlite3_read_only' => env('DB_READ_ONLY', false),
        'sqlite3_encryption_key' => env('DB_ENCRYPTION_KEY', ''),
        'sqlite3_busy_timeout' => env('DB_BUSY_TIMEOUT', 100)
    ],
    'debug' => env('DB_DEBUG', false)
];
