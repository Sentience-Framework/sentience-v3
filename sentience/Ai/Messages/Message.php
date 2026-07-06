<?php

namespace Sentience\Ai\Messages;

class Message
{
    public array $content;

    public function __construct(
        public Role $role,
        string|array $content
    ) {
        $this->content = (array) $content;
    }
}
