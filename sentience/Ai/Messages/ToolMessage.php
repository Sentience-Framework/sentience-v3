<?php

namespace Sentience\Ai\Messages;

class ToolMessage extends Message
{
    public function __construct(
        string $content,
        public string $toolCallId
    ) {
        parent::__construct(Role::Tool, $content);
    }
}
