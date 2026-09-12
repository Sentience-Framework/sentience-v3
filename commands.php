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
            $table = 'migrations';

            Stdio::printLn(
                Json::encode(
                    $db->informationSchemaTables(),
                    JSON_PRETTY_PRINT
                )
            );
            Stdio::printLn(
                Json::encode(
                    $db->informationSchemaColumns($table),
                    JSON_PRETTY_PRINT
                )
            );
            Stdio::printLn(
                Json::encode(
                    $db->informationSchemaPrimaryKeys($table),
                    JSON_PRETTY_PRINT
                )
            );
            Stdio::printLn(
                Json::encode(
                    $db->informationSchemaUniqueConstraints($table),
                    JSON_PRETTY_PRINT
                )
            );
            Stdio::printLn(
                Json::encode(
                    $db->informationSchemaForeignKeyConstraints($table),
                    JSON_PRETTY_PRINT
                )
            );
            Stdio::printLn(
                Json::encode(
                    $db->informationSchemaIndexes($table),
                    JSON_PRETTY_PRINT
                )
            );
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
    ),

    Command::register(
        'information_schema',
        function (DB $db): void {
            $table = 'books';

            $db->dropTable('books')->ifExists()->execute();
            $db->dropTable('authors')->ifExists()->execute();
            $db->dropTable('publishers')->ifExists()->execute();

            $db->createTable('publishers')
                ->identity('id')
                ->string('name')
                ->execute();

            $db->createTable('authors')
                ->identity('id')
                ->string('name')
                ->uniqueConstraint(['id', 'name'])
                ->execute();

            $db->createTable('books')
                ->identity('id')
                ->string('name')
                ->int('author_id')
                ->string('author_name')
                ->int('publisher_id')
                ->uniqueConstraint(['name'])
                ->foreignKeyConstraint(
                    ['author_id', 'author_name'],
                    'authors',
                    ['id', 'name'],
                    'author_fk',
                    ReferentialActionEnum::Cascade,
                    ReferentialActionEnum::NoAction
                )
                ->foreignKeyConstraint(
                    'publisher_id',
                    'publishers',
                    'id',
                    'publisher_fk',
                    ReferentialActionEnum::Cascade,
                    ReferentialActionEnum::SetNull
                )
                ->execute();

            $db->createIndex('books', 'idx_books')
                ->columns(['name', 'author_id'])
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

            $db->dropIndex('books', 'idx_books')->ifExists()->execute();
            $db->dropTable('books')->ifExists()->execute();
            $db->dropTable('authors')->ifExists()->execute();
            $db->dropTable('publishers')->ifExists()->execute();
        }
    )
];
