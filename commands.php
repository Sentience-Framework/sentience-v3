<?php

use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Helpers\Json;
use Sentience\ORM\Database\DB;
use Sentience\Routers\Command;
use Sentience\Sentience\Stdio;
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
        'select',
        [ExampleController::class, 'select']
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
        'emulated-upsert',
        [ExampleController::class, 'emulatedUpsert']
    ),

    Command::register(
        'transactions',
        [ExampleController::class, 'transactions']
    ),

    Command::register(
        'test',
        function (DB $db): void {
            print_r(
                $db->select('migrations')
                    ->whereRegex('filename', 'Dam', 'i')
                    ->execute()
                    ->fetchObjects()
            );
        }
    ),

    Command::register(
        'information_schema',
        function (DB $db): void {
            $table = 'test_migrations';

            $db->createTable('test_fk')
                ->identity('id')
                ->execute();

            $db->createTable('test_migrations')
                ->identity('id')
                ->int('batch', 64, true)
                ->string('filename', 255, true)
                ->dateTime('applied_at', 6, true)
                ->uniqueConstraint(['filename'])
                ->foreignKeyConstraint('batch', 'test_fk', 'id', null, ReferentialActionEnum::Cascade, ReferentialActionEnum::NoAction)
                ->execute();

            $db->createIndex('test_migrations', 'idx_test_migrations')
                ->columns(['filename', 'applied_at'])
                ->execute();

            Stdio::printLn('Tables:');
            Stdio::printLn(Json::encode($db->informationSchemaTables(), JSON_PRETTY_PRINT));

            Stdio::print(PHP_EOL);
            Stdio::printLn('Columns:');
            Stdio::printLn(Json::encode($db->informationSchemaColumns($table), JSON_PRETTY_PRINT));

            Stdio::print(PHP_EOL);
            Stdio::printLn('Primary keys:');
            Stdio::printLn(Json::encode($db->informationSchemaPrimaryKeys($table), JSON_PRETTY_PRINT));

            Stdio::print(PHP_EOL);
            Stdio::printLn('Unique constraints:');
            Stdio::printLn(Json::encode($db->informationSchemaUniqueConstraints($table), JSON_PRETTY_PRINT));

            Stdio::print(PHP_EOL);
            Stdio::printLn('Foreign key constraints:');
            Stdio::printLn(Json::encode($db->informationSchemaForeignKeyConstraints($table), JSON_PRETTY_PRINT));

            Stdio::print(PHP_EOL);
            Stdio::printLn('Indexes:');
            Stdio::printLn(Json::encode($db->informationSchemaIndexes($table), JSON_PRETTY_PRINT));

            $db->dropIndex('test_migrations', 'idx_test_migrations')->ifExists()->execute();
            $db->dropTable('test_migrations')->ifExists()->execute();
            $db->dropTable('test_fk')->ifExists()->execute();
        }
    )
];
