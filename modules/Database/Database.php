<?php

namespace Modules\Database;

use DateTime;
use DateTimeImmutable;
use Throwable;
use Modules\Database\Adapters\AdapterInterface;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\AlterModel;
use Modules\Database\Queries\AlterTable;
use Modules\Database\Queries\CreateModel;
use Modules\Database\Queries\CreateTable;
use Modules\Database\Queries\Delete;
use Modules\Database\Queries\DeleteModels;
use Modules\Database\Queries\DropModel;
use Modules\Database\Queries\DropTable;
use Modules\Database\Queries\Insert;
use Modules\Database\Queries\InsertModels;
use Modules\Database\Queries\Objects\Alias;
use Modules\Database\Queries\Objects\QueryWithParams;
use Modules\Database\Queries\Objects\Raw;
use Modules\Database\Queries\Select;
use Modules\Database\Queries\SelectModels;
use Modules\Database\Queries\Update;
use Modules\Database\Queries\UpdateModels;
use Modules\Database\Results\ResultsInterface;
use Modules\Helpers\Log;
use Modules\Models\Model;
use Modules\Timestamp\Timestamp;

class Database
{
    protected AdapterInterface $adapter;
    protected DialectInterface $dialect;

    public function __construct(
        protected Driver $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
        protected bool $debug,
        protected array $options
    ) {
        $adapter = $driver->getAdapter();
        $dialect = $driver->getDialect();

        $this->adapter = new $adapter(
            $driver,
            $host,
            $port,
            $name,
            $username,
            $password,
            $dialect,
            $debug ? function (string $query, float $startTime, ?string $error = null): void {
                $endTime = microtime(true);

                $lines = [
                    sprintf('Timestamp : %s', date('Y-m-d H:i:s')),
                    sprintf('Query     : %s', $query),
                    sprintf('Time      : %.2f ms', ($endTime - $startTime) * 1000)
                ];

                if ($error) {
                    $lines[] = sprintf('Error     : %s', $error);
                }

                Log::stderrBetweenEqualSigns('Query', $lines);
            } : null,
            $options
        );

        $this->dialect = $dialect;
    }

    public function query(string $query): void
    {
        $this->adapter->query($query);
    }

    public function prepared(string $query, array $params = []): ResultsInterface
    {
        return $this->queryWithParams(new QueryWithParams($query, $params));
    }

    public function queryWithParams(QueryWithParams $queryWithParams): ResultsInterface
    {
        return $this->adapter->queryWithParams($queryWithParams);
    }

    public function beginTransaction(): bool
    {
        return $this->adapter->beginTransaction();
    }

    public function inTransaction(): bool
    {
        return $this->adapter->inTransaction();
    }

    public function commitTransaction(): bool
    {
        return $this->adapter->commitTransaction();
    }

    public function rollbackTransaction(): bool
    {
        return $this->adapter->rollbackTransaction();
    }

    public function transactionInCallback(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);

            $this->commitTransaction();

            return $result;
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }
    }

    public function lastInsertId(?string $name = null): ?string
    {
        return $this->adapter->lastInsertId($name);
    }

    public function escapeIdentifier(string|array|Raw $identifier): string
    {
        return $this->dialect->escapeIdentifier($identifier);
    }

    public function escapeString(string $string): string
    {
        return $this->dialect->escapeString($string);
    }

    public function castToDriver(mixed $value): string
    {
        return $this->dialect->castToDriver($value);
    }

    public function parseFromDriver(mixed $value, string $to): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return match ($to) {
            'bool' => $this->dialect->parseBool($value),
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            Timestamp::class => $this->dialect->parseTimestamp($value),
            DateTime::class => $this->dialect->parseTimestamp($value)->toDateTime(),
            DateTimeImmutable::class => $this->dialect->parseTimestamp($value)->toDateTimeImmutable(),
            default => $value
        };
    }

    public function select(string|array|Alias|Raw $table): Select
    {
        return new Select($this, $this->dialect, $table);
    }

    public function selectModels(string $model): SelectModels
    {
        return new SelectModels($this, $this->dialect, $model);
    }

    public function insert(string|array|Alias|Raw $table = null): Insert
    {
        return new Insert($this, $this->dialect, $table);
    }

    public function insertModels(array|Model $models): InsertModels
    {
        return new InsertModels($this, $this->dialect, $models);
    }

    public function update(string|array|Alias|Raw $table = null): Update
    {
        return new Update($this, $this->dialect, $table);
    }

    public function updateModels(array|Model $models): UpdateModels
    {
        return new UpdateModels($this, $this->dialect, $models);
    }

    public function delete(string|array|Alias|Raw $table): Delete
    {
        return new Delete($this, $this->dialect, $table);
    }

    public function deleteModels(array|Model $models): DeleteModels
    {
        return new DeleteModels($this, $this->dialect, $models);
    }

    public function createTable(string|array|Alias|Raw $table): CreateTable
    {
        return new CreateTable($this, $this->dialect, $table);
    }

    public function createModel(string $model): CreateModel
    {
        return new CreateModel($this, $this->dialect, $model);
    }

    public function alterTable(string|array|Alias|Raw $table): AlterTable
    {
        return new AlterTable($this, $this->dialect, $table);
    }

    public function alterModel(string $model): AlterModel
    {
        return new AlterModel($this, $this->dialect, $model);
    }

    public function dropTable(string|array|Alias|Raw $table): DropTable
    {
        return new DropTable($this, $this->dialect, $table);
    }

    public function dropModel(string $model): DropModel
    {
        return new DropModel($this, $this->dialect, $model);
    }
}
