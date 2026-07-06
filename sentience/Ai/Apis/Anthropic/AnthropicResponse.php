<?php

namespace Sentience\Ai\Apis\Anthropic;

use GuzzleHttp\Psr7\Response;
use Sentience\Ai\Apis\ResponseAbstract;
use Sentience\Ai\Apis\ToolCall;

class AnthropicResponse extends ResponseAbstract
{
    protected array $response = [];

    public function __construct(Response $response)
    {
        $this->response = json_decode($response->getBody()->getContents(), true);
    }

    public function getContent(): string
    {
        foreach ($this->response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                return $block['text'];
            }
        }

        return '';
    }

    public function getReasoningContent(): string
    {
        return '';
    }

    public function getToolCalls(): array
    {
        $toolCalls = [];

        foreach ($this->response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                $toolCalls[] = new ToolCall(
                    $block['id'],
                    $block['name'],
                    $block['input'] ?? []
                );
            }
        }

        return $toolCalls;
    }

    public function getFinishReason(): string
    {
        return $this->response['stop_reason'] ?? 'end_turn';
    }
}
