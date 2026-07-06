<?php

namespace Sentience\Ai\Messages;

class ToolMessage
{
    public Role $role = Role::Tool;

    public function __construct(
        public string $id,
        public string $content,
    ) {
    }
}
