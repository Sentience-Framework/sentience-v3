<?php

namespace Sentience\Ai\Apis\OpenAI;

use GuzzleHttp\Psr7\Response;
use Sentience\Ai\Apis\ResponseAbstract;
use Sentience\Ai\Apis\ToolCall;

class OpenAIResponse extends ResponseAbstract
{
    protected array $response = [];

    public function __construct(Response $response)
    {
        $this->response = json_decode($response->getBody()->getContents(), true);
    }

    public function getContent(): string
    {
        return $this->response['choices'][0]['message']['content'];
    }

    public function getReasoningContent(): string
    {
        return $this->response['choices'][0]['message']['reasoning_content'];
    }

    public function getToolCalls(): array
    {
        $toolCalls = [];

        foreach ($this->response['choices'][0]['message']['tool_calls'] ?? [] as $toolCall) {
            $toolCallId = $toolCall['id'];
            $name = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);

            $toolCalls[] = new ToolCall($toolCallId, $name, $arguments);
        }

        return $toolCalls;
    }

    public function getFinishReason(): string
    {
        return $this->response['choices'][0]['finish_reason'];
    }
}
