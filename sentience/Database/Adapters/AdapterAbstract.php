<?php

namespace Sentience\Database\Adapters;

use Closure;
use Throwable;
use Sentience\Database\Driver;
use Sentience\Helpers\Strings;

abstract class AdapterAbstract implements AdapterInterface
{
    public const string REGEXP_FUNCTION = 'REGEXP';
    public const int REGEXP_FUNCTION_PARAMETER_COUNT = 2;
    public const string OPTIONS_MYSQL_CHARSET = 'charset';
    public const string OPTIONS_SQLITE_JOURNAL_MODE = 'journal_mode';
    public const string OPTIONS_SQLITE_FOREIGN_KEYS = 'foreign_keys';

    public function __construct(
        protected Driver $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
        protected array $queries,
        protected array $options,
        protected ?Closure $debug
    ) {
    }

    protected function regexpFunction(string $pattern, string $value): bool
    {
        return preg_match(
            sprintf(
                '/%s/u',
                Strings::escapeChars($pattern, ['/'])
            ),
            $value
        );
    }

    protected function sqliteJournalMode(string $journalMode): void
    {
        $this->query(
            sprintf(
                'PRAGMA journal_mode=%s;',
                $journalMode
            )
        );
    }

    protected function sqliteForeignKeys(bool $foreignKeys): void
    {
        if (!$foreignKeys) {
            return;
        }

        $this->query('PRAGMA foreign_keys=ON;');
    }

    protected function debug(string $query, float $start, null|string|Throwable $error = null): void
    {
        if (!$this->debug) {
            return;
        }

        if ($error instanceof Throwable) {
            $error = $error->getMessage();
        }

        ($this->debug)($query, $start, $error);
    }
}
