<?php

namespace Sentience\DataLayer\Database\Queries;

use Sentience\Database\Dialects\SQLiteDialect;
use Sentience\Helpers\Arrays;
use Sentience\Database\Dialects\MySQLDialect;
use Sentience\Database\Dialects\PgSQLDialect;
use Sentience\Database\Queries\Objects\Column;

class CreateTableQuery extends \Sentience\Database\Queries\CreateTableQuery
{
    public function bool(string $column, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, __FUNCTION__, $notNull, $default);
    }

    public function int(string $column, int $size = 64, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, __FUNCTION__, $notNull, $default, $size);
    }

    public function autoIncrement(string $column, int $size = 64): static
    {
        if (!in_array($column, $this->primaryKeys)) {
            $this->primaryKeys[] = $column;
        }

        return $this->addColumn($column, __FUNCTION__, true, null, $size);
    }

    public function float(string $column, int $size = 64, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, __FUNCTION__, $notNull, $default, $size);
    }

    public function string(string $column, int $size = 255, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, __FUNCTION__, $notNull, $default, $size);
    }

    public function dateTime(string $column, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, __FUNCTION__, $notNull, $default);
    }

    protected function addColumn(string $column, string $type, bool $notNull, mixed $default, ?int $size = null): static
    {
        $this->columns[] = match (true) {
            $this->dialect instanceof MySQLDialect => $this->getColumnMySQL(
                $column,
                $type,
                $notNull,
                $default,
                $size
            ),
            $this->dialect instanceof PgSQLDialect => $this->getColumnPgSQL(
                $column,
                $type,
                $notNull,
                $default,
                $size
            ),
            $this->dialect instanceof SQLiteDialect => $this->getColumnSQLite(
                $column,
                $type,
                $notNull,
                $default,
                $size
            ),
            default => $this->getColumnSQL(
                $column,
                $type,
                $notNull,
                $default,
                $size
            )
        };

        return $this;
    }

    protected function getColumnMySQL(string $column, string $type, bool $notNull, mixed $default, ?int $size = null): Column
    {
        $columnType = match ($type) {
            'bool' => 'TINYINT',
            'int',
            'autoIncrement' => $size > 32 ? 'BIGINT' : 'INT',
            'float' => 'FLOAT',
            'string' => $size > 255 ? 'LONGTEXT' : sprintf('VARCHAR(%d)', $size),
            'dateTime' => 'DATETIME(6)',
            default => 'VARCHAR(255)'
        };

        return new Column(
            $column,
            $columnType,
            $notNull,
            $default,
            $type == 'autoIncrement' ? ['AUTO_INCREMENT'] : []
        );
    }

    protected function getColumnPgSQL(string $column, string $type, bool $notNull, mixed $default, ?int $size = null): Column
    {
        $columnType = match ($type) {
            'bool' => 'BOOLEAN',
            'int' => $size > 32 ? 'INT8' : 'INT4',
            'autoIncrement' => $size > 32 ? 'BIGSERIAL' : 'SERIAL',
            'float' => $size > 32 ? 'FLOAT8' : 'FLOAT4',
            'string' => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size),
            'dateTime' => 'TIMESTAMP',
            default => 'TEXT'
        };

        return new Column(
            $column,
            $columnType,
            $notNull,
            $default,
            []
        );
    }

    protected function getColumnSQLite(string $column, string $type, bool $notNull, mixed $default, ?int $size = null): Column
    {
        $columnType = match ($type) {
            'bool' => 'BOOLEAN',
            'int',
            'autoIncrement' => $size > 32 ? 'BIGINT' : 'INTEGER',
            'float' => "REAL",
            'string' => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size),
            'dateTime' => 'DATETIME',
            default => 'TEXT'
        };

        return new Column(
            $column,
            $columnType,
            $notNull,
            $default,
            []
        );
    }

    protected function getColumnSQL(string $column, string $type, bool $notNull, mixed $default, ?int $size = null): Column
    {
        $columnType = match ($type) {
            'bool' => 'INT',
            'int',
            'autoIncrement' => 'INT',
            'float' => "FLOAT",
            'string' => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size),
            'dateTime' => 'DATETIME',
            default => 'VARCHAR(255)'
        };

        return new Column(
            $column,
            $columnType,
            $notNull,
            $default,
            []
        );
    }

    public function primaryKeys(array|string $columns): static
    {
        $this->primaryKeys = Arrays::unique([
            ...$this->primaryKeys,
            ...(array) $columns
        ]);

        return $this;
    }
}
