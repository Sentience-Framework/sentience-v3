<?php

namespace Sentience\Ai\Messages;

use Sentience\Ai\StructuredOutput\StructuredOutputInterface;

class StructuredOutputMessage extends Message
{
    public function __construct(string $content)
    {
        parent::__construct(Role::System, $content);
    }
}
