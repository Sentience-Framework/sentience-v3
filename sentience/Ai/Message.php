<?php

namespace Sentience\Ai;

class Message
{
    public function __construct(
        public Role $role,
        public string $content
    ) {
    }
}
