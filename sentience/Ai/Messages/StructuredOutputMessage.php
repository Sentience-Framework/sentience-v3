<?php

namespace Sentience\Ai\Messages;

class StructuredOutputMessage extends Message
{
    public function __construct(string $content)
    {
        parent::__construct(Role::System, [$content]);
    }
}
