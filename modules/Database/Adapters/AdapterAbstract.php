<?php

namespace Modules\Database\Adapters;

use Closure;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Driver;

abstract class AdapterAbstract implements AdapterInterface
{
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
}
