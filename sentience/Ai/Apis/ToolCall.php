<?php

namespace Sentience\Ai\Apis;

class ToolCall
{
    public function __construct(
        public string $toolCallId,
        public string $name,
        public array $arguments
    ) {
    }
}
