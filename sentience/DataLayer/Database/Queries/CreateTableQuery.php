<?php

namespace Sentience\DataLayer\Database\Queries;

use DateTime;
use Sentience\Database\Dialects\MySQLDialect;
use Sentience\Database\Dialects\PgSQLDialect;
use Sentience\Database\Dialects\SQLiteDialect;
use Sentience\Helpers\Arrays;

class CreateTableQuery extends \Sentience\Database\Queries\CreateTableQuery
{
    public function bool(string $column, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, 'bool', $notNull, $default);
    }

    public function int(string $column, int $bits = 64, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, 'int', $notNull, $default, $bits);
    }

    public function autoIncrement(string $column, int $bits = 32): static
    {
        if (!in_array($column, $this->primaryKeys)) {
            $this->primaryKeys[] = $column;
        }

        return $this->addColumn($column, 'int', true, null, $bits, true);
    }

    public function float(string $column, int $bits = 64, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, 'float', $notNull, $default, $bits);
    }

    public function string(string $column, int $size = 255, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, 'string', $notNull, $default, $size);
    }

    public function dateTime(string $column, bool $notNull = false, mixed $default = null): static
    {
        return $this->addColumn($column, DateTime::class, $notNull, $default);
    }

    protected function addColumn(
        string $column,
        string $type,
        bool $notNull,
        mixed $default,
        ?int $size = null,
        bool $generatedByDefaultAsIdentity = false
    ): static {
        match (true) {
            $this->dialect instanceof MySQLDialect => $this->getColumnMySQL(
                $column,
                $type,
                $notNull,
                $default,
                $size,
                $generatedByDefaultAsIdentity
            ),
            $this->dialect instanceof PgSQLDialect => $this->getColumnPgSQL(
                $column,
                $type,
                $notNull,
                $default,
                $size,
                $generatedByDefaultAsIdentity
            ),
            $this->dialect instanceof SQLiteDialect => $this->getColumnSQLite(
                $column,
                $type,
                $notNull,
                $default,
                $size,
                $generatedByDefaultAsIdentity
            ),
            default => $this->getColumnSQL(
                $column,
                $type,
                $notNull,
                $default,
                $size,
                $generatedByDefaultAsIdentity
            )
        };

        return $this;
    }

    protected function getColumnMySQL(
        string $column,
        string $type,
        bool $notNull,
        mixed $default,
        ?int $size = null,
        bool $generatedByDefaultAsIdentity = false
    ): void {
        $columnType = match ($type) {
            'bool' => 'TINYINT',
            'float' => 'FLOAT',
            'string' => $this->getTextColumnTypeMySQL($size),
            DateTime::class => 'DATETIME(6)',
            default => 'VARCHAR(255)'
        };

        $this->column(
            $column,
            $columnType,
            $notNull,
            $default,
            $generatedByDefaultAsIdentity
        );
    }

    protected function getColumnPgSQL(
        string $column,
        string $type,
        bool $notNull,
        mixed $default,
        ?int $size = null,
        bool $generatedByDefaultAsIdentity = false
    ): void {
        $columnType = match ($type) {
            'bool' => 'BOOLEAN',
            'int' => $this->getIntColumnTypePgSQL($size),
            'float' => $this->getFloatColumnTypePgSQL($size),
            'string' => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size),
            DateTime::class => 'TIMESTAMP',
            default => 'TEXT'
        };

        $this->column(
            $column,
            $columnType,
            $notNull,
            $default,
            $generatedByDefaultAsIdentity
        );
    }

    protected function getColumnSQLite(
        string $column,
        string $type,
        bool $notNull,
        mixed $default,
        ?int $size = null,
        bool $generatedByDefaultAsIdentity = false
    ): void {
        $columnType = match ($type) {
            'bool' => 'BOOLEAN',
            'int',
            'autoIncrement' => $size > 32 ? 'BIGINT' : 'INTEGER',
            'float' => 'FLOAT',
            'string' => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size),
            DateTime::class => 'DATETIME',
            default => 'TEXT'
        };

        $this->column(
            $column,
            $columnType,
            $notNull,
            $default,
            $generatedByDefaultAsIdentity
        );
    }

    protected function getColumnSQL(
        string $column,
        string $type,
        bool $notNull,
        mixed $default,
        ?int $size = null,
        bool $generatedByDefaultAsIdentity = false
    ): void {
        $columnType = match ($type) {
            'bool' => 'INTEGER',
            'int' => 'INTEGER',
            'float' => 'REAL',
            'string' => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size),
            DateTime::class => 'DATETIME',
            default => 'VARCHAR(255)'
        };

        $this->column(
            $column,
            $columnType,
            $notNull,
            $default,
            $generatedByDefaultAsIdentity
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
