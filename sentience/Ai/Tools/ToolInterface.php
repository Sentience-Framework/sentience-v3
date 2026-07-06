<?php

namespace Sentience\Ai\Tools;

interface ToolInterface
{
    public function schema(string $name): array;
    public function execute(array $arguments): string;
}
