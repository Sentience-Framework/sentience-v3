<?php

namespace Sentience\Ai\Messages;

class Message
{
    public function __construct(
        public Role $role,
        public string $content
    ) {
    }
}
