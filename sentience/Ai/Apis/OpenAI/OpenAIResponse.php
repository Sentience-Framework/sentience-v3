<?php

namespace Sentience\Ai\Apis\OpenAI;

use OpenAI\Responses\Completions\CreateResponse;
use Sentience\Ai\Apis\ResponseInterface;

class OpenAIResponse implements ResponseInterface
{
    public function __construct(protected CreateResponse $createResponse)
    {
    }

    public function getContent(): string
    {
        $content = '';

        foreach ($this->createResponse->choices as $choice) {
            $content .= $choice->text;
        }

        return $content;
    }

    public function getReasoningContent(): string
    {
        return '';
    }

    public function getToolCalls(): array
    {
        $toolCalls = [];

        // TODO: Finish

        return $toolCalls;
    }
}
