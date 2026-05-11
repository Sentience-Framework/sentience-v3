<?php

use Sentience\Database\Queries\Query;
use Sentience\ORM\Database\DB;
use Sentience\Routers\Command;
use Src\Controllers\DevToolsController;
use Src\Controllers\ExampleController;
use Src\Controllers\SentienceController;

return [
    Command::register(
        'server:start',
        [SentienceController::class, 'startServer']
    ),

    Command::register(
        'migrations:init',
        [SentienceController::class, 'initMigrations']
    ),

    Command::register(
        'migrations:apply',
        [SentienceController::class, 'applyMigrations']
    ),

    Command::register(
        'migrations:rollback',
        [SentienceController::class, 'rollbackMigrations']
    ),

    Command::register(
        'migrations:create',
        [SentienceController::class, 'createMigration']
    ),

    Command::register(
        'models:init',
        [SentienceController::class, 'initModel']
    ),

    Command::register(
        'models:update',
        [SentienceController::class, 'updateModel']
    ),

    Command::register(
        'models:reset',
        [SentienceController::class, 'resetModel']
    ),

    Command::register(
        'dotenv:fix',
        [SentienceController::class, 'fixDotEnv']
    ),

    Command::register(
        'dev-tools:sort-imports',
        [DevToolsController::class, 'sortImports']
    ),

    Command::register(
        'dev-tools:remove-trailing-commas',
        [DevToolsController::class, 'removeTrailingCommas']
    ),

    Command::register(
        'dev-tools:remove-excessive-whitespace',
        [DevToolsController::class, 'removeExcessiveWhitespace']
    ),

    Command::register(
        'example',
        [ExampleController::class, 'cliExample']
    ),

    Command::register(
        'query',
        [ExampleController::class, 'query']
    ),

    Command::register(
        'crud',
        [ExampleController::class, 'crud']
    ),

    Command::register(
        'select',
        [ExampleController::class, 'select']
    ),

    Command::register(
        'transactions',
        [ExampleController::class, 'transactions']
    ),

    Command::register(
        'mapper',
        [ExampleController::class, 'mapper']
    ),

    Command::register(
        'fk',
        [ExampleController::class, 'fk']
    ),

    Command::register(
        'test',
        function (DB $db): void {
            $db->insert('migrations')
                ->values(
                    [
                        'filename' => 'test1',
                        'batch' => 1,
                        'applied_at' => Query::now()
                    ],
                    [
                        'filename' => 'test2',
                        'batch' => 1,
                        'applied_at' => Query::now()
                    ],
                    [
                        'filename' => 'test3',
                        'batch' => 1,
                        'applied_at' => Query::now()
                    ],
                    [
                        'filename' => 'test4',
                        'batch' => 1,
                        'applied_at' => Query::now()
                    ]
                )
                ->onConflictDoUpdate(['filename'])
                ->emulateOnConflict('id')
                ->execute();
        }
    ),

    Command::register(
        'db:create',
        function (DB $db): void {
            $sql = $db->createTable('migrations')
                ->ifNotExists()
                ->column('id', 'INTEGER', true, null, true)
                ->column('batch', 'INTEGER')
                ->column('filename', 'VARCHAR(255)')
                ->column('applied_at', 'TIMESTAMP')
                ->primaryKeys(['id'])
                ->uniqueConstraint(['filename'])
                ->toSql();

            print_r(
                $db->query('EXPLAIN ' . $sql)->fetchAssocs()
            );
        }
    ),

    Command::register(
        'drivers',
        function (): void {
            print_r(PDO::getAvailableDrivers());
        }
    ),

    Command::register(
        'table',
        [ExampleController::class, 'table']
    )
];
