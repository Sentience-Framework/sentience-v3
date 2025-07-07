<?php

namespace src\controllers;

use src\database\Database;
use src\database\queries\Query;
use src\exceptions\MigrationException;
use src\migrations\MigrationFactory;
use src\models\Migration;
use src\sentience\Stdio;
use src\utils\Filesystem;

class MigrationController extends Controller
{
    protected Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function initMigrations(): void
    {
        $migration = new Migration($this->database);

        $migration->createTable(true);

        Stdio::printLn('Migrations table created');
    }

    public function applyMigrations(): void
    {
        $migrations = $this->getMigrations();

        if (count($migrations) == 0) {
            Stdio::printLn('No migrations found');

            return;
        }

        $highestBatch = $this->getHighestBatch();

        $nextBatch = $highestBatch + 1;

        foreach ($migrations as $filepath) {
            $filename = basename($filepath);

            $alreadyApplied = $database->select()
                ->table(Migration::getTable())
                ->whereEquals('filename', $filename)
                ->exists();

            if ($alreadyApplied) {
                Stdio::printFLn('Migration %s already applied', $filename);

                continue;
            }

            $migration = include $filepath;

            $database->transactionInCallback(function (Database $database) use ($migration): void {
                $migration->apply($database);
            });

            Stdio::printFLn('Migration %s applied', $filename);

            $migrationModel = new Migration($database);
            $migrationModel->batch = $nextBatch;
            $migrationModel->filename = $filename;
            $migrationModel->appliedAt = Query::now();
            $migrationModel->insert();
        }
    }

    public function rollbackMigrations(Database $database): void
    {
        $migrations = $this->getMigrations();

        if (count($migrations) == 0) {
            Stdio::printLn('No migrations found');

            return;
        }

        $highestBatch = $this->getHighestBatch();

        if ($highestBatch == 0) {
            Stdio::printLn('No migrations found to rollback');

            return;
        }

        $migrationsToRevert = $database->select()
            ->table(Migration::getTable())
            ->whereEquals('batch', $highestBatch)
            ->orderByDesc('applied_at')
            ->execute()
            ->fetchAll();

        foreach ($migrationsToRevert as $migrationToRevert) {
            $filename = $migrationToRevert->filename;
            $filepath = Filesystem::path($migrationsDir, $filename);

            if (!file_exists($filepath)) {
                throw new MigrationException('unable to find migration %s', $filename);
            }

            $migration = include $filepath;

            $database->transactionInCallback(function (Database $database) use ($migration): void {
                $migration->rollback($database);
            });

            Stdio::printFLn('Migration %s rolled back', $filename);

            $database->delete()
                ->table(Migration::getTable())
                ->whereEquals('id', $migrationToRevert->id)
                ->execute();
        }
    }

    public function createMigration(array $words, array $flags): void
    {
        $name = $flags['name'] ?? $words[0] ?? null;

        if (is_null($name)) {
            Stdio::errorLn('Please provide a name for the migration');

            return;
        }

        $timestamp = date('YmdHis');

        $migrationFilename = sprintf('%s_%s.php', $timestamp, $name);

        $migrationFilepath = Filesystem::path(
            SENTIENCE_DIR,
            'migrations',
            $migrationFilename
        );

        $migrationFileContents = MigrationFactory::create();

        file_put_contents($migrationFilepath, $migrationFileContents);

        Stdio::printFLn('Migration %s created successfully', $migrationFilename);
    }

    protected function getMigrations(): array
    {
        $migrationsDir = Filesystem::path(SENTIENCE_DIR, 'migrations');

        return array_values(
            array_filter(
                Filesystem::scandir($migrationsDir),
                function (string $path): bool {
                    if (!str_ends_with(strtolower($path), '.php')) {
                        return false;
                    }

                    if (!preg_match('/[0-9]{14}[\-\_][a-zA-Z0-9\-\_]+\.php$/', $path)) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    protected function getHighestBatch(): int
    {
        return $this->database->select()
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
    }
}
