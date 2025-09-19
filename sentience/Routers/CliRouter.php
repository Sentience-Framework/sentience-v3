<?php

namespace Sentience\Routers;

use Sentience\Sentience\Argv;

class CliRouter
{
    public array $commands = [];

    public function __construct()
    {
    }

    public function bind(Command $command): void
    {
        $this->commands[] = $command;
    }

    public function match(Argv $argv): array
    {
        $arg = $argv->argv[1] ?? null;

        foreach ($this->commands as $command) {
            if ($arg == $command->command) {
                $args = $argv->getArgs();

                [$words, $flags] = $this->parseArgs($args);

                return [$command, $words, $flags];
            }
        }

        return [null, null, null];
    }

    protected function parseArgs(array $args): array
    {
        $words = [];
        $flags = [];

        foreach ($args as $arg) {
            $isMatch = preg_match('/\-\-(.[^\=]*)\=?(.*)/', (string) $arg, $matches);

            if (!$isMatch) {
                $words[] = $arg;

                continue;
            }

            $key = $matches[1];
            $value = $matches[2] ?? '';

            $flags[$key] = $value;
        }

        return [$words, $flags];
    }
}
