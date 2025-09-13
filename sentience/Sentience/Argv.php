<?php

namespace Sentience\Sentience;

use Sentience\Abstracts\Singleton;

class Argv extends Singleton
{
    protected static function createInstance(): static
    {
        $argv = $GLOBALS['argv'];

        $file = $argv[0];
        $command = $argv[1] ?? '';
        $args = array_slice($argv, 2);

        return new static($file, $command, $args);
    }

    public function __construct(
        public string $file,
        public string $command,
        public array $args
    ) {
    }
}
