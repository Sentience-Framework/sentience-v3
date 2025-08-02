<?php

use sentience\Routers\Command;
use src\controllers\DevToolsController;
use src\controllers\ExampleController;
use src\controllers\MigrationController;
use src\controllers\ModelController;
use src\controllers\SentienceController;

return [
    Command::register(
        'server:start',
        [SentienceController::class, 'startServer']
    ),

    Command::register(
        'migrations:init',
        [MigrationController::class, 'initMigrations']
    ),

    Command::register(
        'migrations:apply',
        [MigrationController::class, 'applyMigrations']
    ),

    Command::register(
        'migrations:rollback',
        [MigrationController::class, 'rollbackMigrations']
    ),

    Command::register(
        'migrations:create',
        [MigrationController::class, 'createMigration']
    ),

    Command::register(
        'models:init',
        [ModelController::class, 'initModel']
    ),

    Command::register(
        'models:update',
        [ModelController::class, 'updateModel']
    ),

    Command::register(
        'models:reset',
        [ModelController::class, 'resetModel']
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
    )
];
