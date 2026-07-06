<?php

namespace Sentience\Ai\Messages;

use Sentience\Ai\Apis\ResponseInterface;

class AssistantMessage extends Message
{
    public static function fromResponse(ResponseInterface $response): static
    {
        return new static(
            $response->getContent(),
            $response->getReasoningContent(),
            $response->getToolCalls()
        );
    }

    public function __construct(
        string $content,
        public string $reasoningContent,
        public array $toolCalls
    ) {
        parent::__construct(Role::Assistant, $content);
    }
}
