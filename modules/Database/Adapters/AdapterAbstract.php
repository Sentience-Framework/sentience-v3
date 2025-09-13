<?php

namespace Modules\Database\Adapters;

use Closure;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Driver;
use Modules\Helpers\Strings;

abstract class AdapterAbstract implements AdapterInterface
{
    public const REGEXP_FUNCTION = 'REGEXP';

    public function __construct(
        protected Driver $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
        protected DialectInterface $dialect,
        protected ?Closure $debug,
        protected array $options
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
}
