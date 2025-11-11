<?php

namespace Sentience\DataLayer\Database\Queries;

use Sentience\Database\Dialects\MySQLDialect;
use Sentience\Database\Dialects\PgSQLDialect;
use Sentience\Database\Dialects\SQLiteDialect;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Helpers\Arrays;

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

    public function autoIncrement(string $column, int $size = 32): static
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
            'string' => $this->getTextColumnTypeMySQL($size),
            'dateTime' => 'DATETIME(6)',
            default => 'VARCHAR(255)'
        };

        return new Column(
            $column,
            $columnType,
            $notNull,
            $default,
            $type == 'autoIncrement'
        );
    }

    protected function getColumnPgSQL(string $column, string $type, bool $notNull, mixed $default, ?int $size = null): Column
    {
        $columnType = match ($type) {
            'bool' => 'BOOLEAN',
            'int' => $this->getIntColumnTypePgSQL($size),
            'autoIncrement' => $this->getIntColumnTypePgSQL($size),
            'float' => $this->getFloatColumnTypePgSQL($size),
            'string' => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size),
            'dateTime' => 'TIMESTAMP',
            default => 'TEXT'
        };

        return new Column(
            $column,
            $columnType,
            $notNull,
            $default,
            $type == 'autoIncrement'
        );
    }

    protected function getColumnSQLite(string $column, string $type, bool $notNull, mixed $default, ?int $size = null): Column
    {
        $columnType = match ($type) {
            'bool' => 'BOOLEAN',
            'int',
            'autoIncrement' => $size > 32 ? 'INTEGER' : 'INTEGER',
            'float' => 'FLOAT',
            'string' => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size),
            'dateTime' => 'DATETIME',
            default => 'TEXT'
        };

        return new Column(
            $column,
            $columnType,
            $notNull,
            $default,
            $type == 'autoIncrement'
        );
    }

    protected function getColumnSQL(string $column, string $type, bool $notNull, mixed $default, ?int $size = null): Column
    {
        $columnType = match ($type) {
            'bool' => 'INT',
            'int',
            'autoIncrement' => 'INT',
            'float' => 'FLOAT',
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

    protected function getTextColumnTypeMySQL(int $size): string
    {
        if ($size <= 255) {
            return sprintf('VARCHAR(%d)', $size);
        }

        if ($size <= 65535) {
            return 'TEXT';
        }

        if ($size <= 16777215) {
            return 'MEDIUMTEXT';
        }

        return 'LONGTEXT';
    }

    protected function getIntColumnTypePgSQL(int $size): string
    {
        if ($size <= 16) {
            return 'INT2';
        }

        if ($size <= 32) {
            return 'INT4';
        }

        return 'INT8';
    }

    protected function getFloatColumnTypePgSQL(int $size): string
    {
        if ($size <= 16) {
            return 'FLOAT2';
        }

        if ($size <= 32) {
            return 'FLOAT4';
        }

        return 'FLOAT8';
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
