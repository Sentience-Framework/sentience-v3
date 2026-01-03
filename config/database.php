<?php

use Sentience\Helpers\Filesystem;

return [
    'driver' => env('DB_DRIVER', 'sqlite'),
    'settings' => [
        'firebird' => [
            'file' => env('DB_FILE', 'sentience.fdb'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3050),
            'username' => env('DB_USERNAME', 'SYSDBA'),
            'password' => env('DB_PASSWORD', ''),
            'queries' => env('DB_QUERIES', []),
            'use_pdo' => env('DB_USE_PDO', false)
        ],
        'mariadb' => [
            'name' => env('DB_NAME', 'sentience'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'unix_socket' => env('DB_UNIX_SOCKET', null),
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
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'engine' => env('DB_ENGINE', 'InnoDB'),
            'use_pdo' => env('DB_USE_PDO', false),
            'version' => env('DB_VERSION')
        ],
        'mysql' => [
            'name' => env('DB_NAME', 'sentience'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'unix_socket' => env('DB_UNIX_SOCKET', null),
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
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'engine' => env('DB_ENGINE', 'InnoDB'),
            'use_pdo' => env('DB_USE_PDO', false),
            'version' => env('DB_VERSION')
        ],
        'oci' => [
            'dsn' => env('DB_DSN', ''),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'queries' => env('DB_QUERIES', [])
        ],
        'pgsql' => [
            'name' => env('DB_NAME', 'sentience'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'unix_socket' => env('DB_UNIX_SOCKET', null),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),

            /**
             * Highly expirimental!
             *
             * Asynchronous writes:
             * 'queries' => ['SET synchronous_commit TO OFF;']
             */
            'queries' => env('DB_QUERIES', []),

            'client_encoding' => env('DB_CLIENT_ENCODING', 'UTF8'),
            'search_path' => env('DB_SEARCH_PATH', 'public'),
            'use_pdo' => env('DB_USE_PDO', false),
            'version' => env('DB_VERSION')
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
            'optimize' => env('DB_SQLITE_OPTIMIZE', true),

            /**
             * The SQLite3 class contains a bug that executes queries multiple times.
             * For now it is recommended to use the PDO adapter.
             */
            'use_pdo' => env('DB_USE_PDO', true),
            'version' => env('DB_VERSION')
        ],
        'sqlsrv' => [
            'name' => env('DB_NAME', 'sentience'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 5432),
            'username' => env('DB_USERNAME', 'sa'),
            'password' => env('DB_PASSWORD', ''),
            'queries' => env('DB_QUERIES', []),

            'encrypt' => env('DB_ENCRYPT', true),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', false),

            'use_pdo' => env('DB_USE_PDO', false),
            'version' => env('DB_VERSION')
        ]
    ],

    /**
     * The only valid reason to enable this option, is for debugging purposes.
     *
     * This option is not set at the adapter level.
     * It needs to be passed in via the query manually by passing config('database->emulate_prepares') to ->execute().
     *
     * This is done because emulating prepares is never recommended due to the safety risks involved.
     *
     * While the developer of Sentience has a lot of faith in his param casting and that of PDO.
     * The chance of an unknown sequence breaking the param injection is never zero.
     */
    'emulate_prepares' => env('DB_EMULATE_PREPARES', false),

    'debug' => env('DB_DEBUG', false)
];
