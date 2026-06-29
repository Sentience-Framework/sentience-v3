<?php

namespace Sentience\Ai\Tools;

interface ToolInterface
{
    public function name(): string;
    public function execute(): string;
}
