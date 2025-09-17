<?php

namespace Sentience\Sentience;

class Argv
{
    public static function createFromArgv(): static
    {
        return new static($GLOBALS['argv']);
    }

    public function __construct(public array $args)
    {
    }
}
