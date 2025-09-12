<?php

namespace Modules\Database\Adapters;

use Closure;
use Modules\Database\Dialects\DialectInterface;

abstract class AdapterAbstract implements AdapterInterface
{
    public function __construct(
        protected string $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
        protected DialectInterface $dialect,
        protected ?Closure $debug
    ) {
    }
}
