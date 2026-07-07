<?php

namespace Sentience\Ai\Tools;

interface ToolInterface
{
    public function schema(): array;
    public function execute(array $arguments): string;
}
