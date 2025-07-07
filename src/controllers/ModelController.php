<?php

namespace src\controllers;

use Throwable;
use src\database\Database;
use src\database\queries\Query;
use src\migrations\MigrationFactory;
use src\models\Migration;
use src\sentience\Stdio;
use src\utils\Filesystem;
use src\utils\Reflector;

class ModelController extends Controller
{
    public function initModel(Database $database, array $words, array $flags): void
    {
        $class = $flags['model'] ?? $words[0] ?? null;

        if (!$class) {
            Stdio::errorLn('No model set');

            return;
        }

        $class = !str_contains('\\', $class)
            ? $class = sprintf('\\src\\models\\%s', $class)
            : $class;

        if (!class_exists($class)) {
            Stdio::errorFLn('Model %s does not exist', $class);

            return;
        }

        $model = new $class($database);

        $migrationName = sprintf(
            '%s_create_%s_table.php',
            date('YmdHis'),
            $model::getTable()
        );

        $migrationFileContents = MigrationFactory::create(
            [
                sprintf('$model = new %s($database);', $class),
                '$model->createTable(true);'
            ],
            [
                sprintf('$model = new %s($database);', $class),
                '$model->dropTable(true);'
            ]
        );

        $migrationFilepath = Filesystem::path(SENTIENCE_DIR, 'migrations', $migrationName);

        file_put_contents($migrationFilepath, $migrationFileContents);

        $migration = include $migrationFilepath;

        try {
            $database->transactionInCallback(function (Database $database) use ($migration): void {
                $migration->apply($database);
            });
        } catch (Throwable $exception) {
            unlink($migrationFilepath);

            throw $exception;
        }

        $highestBatch = $database->select()
            ->table(Migration::getTable())
            ->columns([
                Query::alias(
                    Query::raw('MAX(batch)'),
                    'batch'
                )
            ])
            ->execute()
            ->fetch()
            ->batch ?? 0;

        $nextBatch = $highestBatch + 1;

        $migrationModel = new Migration($database);
        $migrationModel->batch = $nextBatch;
        $migrationModel->filename = $migrationName;
        $migrationModel->appliedAt = Query::now();
        $migrationModel->insert();

        Stdio::printFLn('Migration for model %s created successfully', Reflector::getShortName($model));
    }

    public function updateModel(Database $database, array $words, array $flags): void
    {
        $class = $flags['model'] ?? $words[0] ?? null;

        if (!$class) {
            Stdio::errorLn('No model set');

            return;
        }

        $class = !str_contains('\\', $class)
            ? $class = sprintf('\\src\\models\\%s', $class)
            : $class;

        if (!class_exists($class)) {
            Stdio::errorFLn('Model %s does not exist', $class);

            return;
        }

        $model = new $class($database);

        $migrationName = sprintf(
            '%s_alter_%s_table.php',
            date('YmdHis'),
            $model::getTable()
        );

        $migrationFileContents = MigrationFactory::create(
            [
                sprintf('$model = new %s($database);', $class),
                '$model->alterTable();'
            ],
            [
                sprintf('$model = new %s($database);', $class),
                '$model->alterTable();'
            ]
        );

        $migrationFilepath = Filesystem::path(SENTIENCE_DIR, 'migrations', $migrationName);

        file_put_contents($migrationFilepath, $migrationFileContents);

        $migration = include $migrationFilepath;

        try {
            $database->transactionInCallback(function (Database $database) use ($migration): void {
                $migration->apply($database);
            });
        } catch (Throwable $exception) {
            unlink($migrationFilepath);

            throw $exception;
        }

        $highestBatch = $database->select()
            ->table(Migration::getTable())
            ->columns([
                Query::alias(
                    Query::raw('MAX(batch)'),
                    'batch'
                )
            ])
            ->execute()
            ->fetch()
            ->batch ?? 0;

        $nextBatch = $highestBatch + 1;

        $migrationModel = new Migration($database);
        $migrationModel->batch = $nextBatch;
        $migrationModel->filename = $migrationName;
        $migrationModel->appliedAt = Query::now();
        $migrationModel->insert();

        Stdio::printFLn('Migration for model %s created successfully', Reflector::getShortName($model));
    }

    public function resetModel(Database $database, array $words, array $flags): void
    {
        $class = $flags['model'] ?? $words[0] ?? null;

        if (!$class) {
            Stdio::errorLn('No model set');

            return;
        }

        $class = !str_contains('\\', $class)
            ? $class = sprintf('\\src\\models\\%s', $class)
            : $class;

        if (!class_exists($class)) {
            Stdio::errorFLn('Model %s does not exist', $class);

            return;
        }

        $model = new $class($database);

        $migrationName = sprintf(
            '%s_reset_%s_table.php',
            date('YmdHis'),
            $model::getTable()
        );

        $migrationFileContents = MigrationFactory::create(
            [
                sprintf('$model = new %s($database);', $class),
                '$model->dropTable(true);',
                '$model->createTable(true);'
            ]
        );

        $migrationFilepath = Filesystem::path(SENTIENCE_DIR, 'migrations', $migrationName);

        file_put_contents($migrationFilepath, $migrationFileContents);

        $migration = include $migrationFilepath;

        try {
            $database->transactionInCallback(function (Database $database) use ($migration): void {
                $migration->apply($database);
            });
        } catch (Throwable $exception) {
            unlink($migrationFilepath);

            throw $exception;
        }

        $highestBatch = $database->select()
            ->table(Migration::getTable())
            ->columns([
                Query::alias(
                    Query::raw('MAX(batch)'),
                    'batch'
                )
            ])
            ->execute()
            ->fetch()
            ->batch ?? 0;

        $nextBatch = $highestBatch + 1;

        $migrationModel = new Migration($database);
        $migrationModel->batch = $nextBatch;
        $migrationModel->filename = $migrationName;
        $migrationModel->appliedAt = Query::now();
        $migrationModel->insert();

        Stdio::printFLn('Migration for model %s created successfully', Reflector::getShortName($model));
    }
}
