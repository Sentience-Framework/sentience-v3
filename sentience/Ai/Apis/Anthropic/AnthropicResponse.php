<?php

namespace Sentience\Ai\Apis\Anthropic;

use GuzzleHttp\Psr7\Response;
use Sentience\Ai\Apis\ResponseAbstract;
use Sentience\Ai\Apis\ChunkSize;
use Sentience\Ai\Apis\ToolCall;

class AnthropicResponse extends ResponseAbstract
{
    protected array $response = [];

    // Accumulated state during streaming
    protected string $accumulatedContent = '';
    protected string $accumulatedReasoningContent = '';
    protected array $accumulatedToolCalls = [];
    protected string $accumulatedFinishReason = '';
    protected array $accumulatedToolCallData = [];
    protected string $buffer = '';
    protected bool $streamExhausted = false;

    // The Guzzle Response for streaming
    protected ?Response $guzzleResponse = null;

    // Whether the API is in SSE streaming mode
    protected bool $stream = false;

    public function __construct(null|array|Response $response, bool $hasStructuredOutput = false, bool $stream = false)
    {
        parent::__construct($hasStructuredOutput);

        if (is_array($response)) {
            $this->response = $response;
            return;
        }

        if ($response === null) {
            $this->response = [];
            return;
        }

        $this->guzzleResponse = $response;
        $this->stream = $stream;
    }

    public function getContent(): string
    {
        if (!empty($this->response)) {
            foreach ($this->response['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    return $block['text'];
                }
            }
            return '';
        }

        return $this->accumulatedContent;
    }

    public function getReasoningContent(): string
    {
        if (!empty($this->response)) {
            $reasoning = [];
            foreach ($this->response['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'thinking') {
                    $reasoning[] = $block['thinking'];
                }
            }
            return implode(PHP_EOL, $reasoning);
        }

        return $this->accumulatedReasoningContent;
    }

    public function getToolCalls(): array
    {
        if (!empty($this->response)) {
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

        return $this->accumulatedToolCalls;
    }

    public function getFinishReason(): string
    {
        if (!empty($this->response)) {
            return $this->response['stop_reason'] ?? 'end_turn';
        }

        return $this->accumulatedFinishReason ?: 'end_turn';
    }

    public function readStream(bool $untilEof = false, ChunkSize $chunkSize = ChunkSize::M): void
    {
        if ($this->streamExhausted || $this->guzzleResponse === null) {
            return;
        }

        if (!$this->stream) {
            // Non-streaming mode: read the full body as JSON
            $body = $this->guzzleResponse->getBody();
            $this->response = json_decode($body->getContents(), true) ?: [];
            $this->streamExhausted = true;
            return;
        }

        // Streaming mode: process SSE events
        $body = $this->guzzleResponse->getBody();

        do {
            if ($body->eof()) {
                $this->finalizeStream();
                $this->streamExhausted = true;
                return;
            }

            $this->buffer .= $body->read($chunkSize->value);
            $lines = explode("\n", $this->buffer);
            $this->buffer = array_pop($lines);

            foreach ($lines as $line) {
                $line = rtrim($line, "\r");

                if ($line === '' || str_starts_with($line, 'event: ')) {
                    continue;
                }

                if (!str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = json_decode(substr($line, 6), true);

                if (!is_array($data)) {
                    continue;
                }

                $type = $data['type'] ?? '';

                if ($type === 'content_block_start') {
                    $block = $data['content_block'] ?? [];

                    if (($block['type'] ?? '') === 'text' && isset($block['text'])) {
                        $this->accumulatedContent .= $block['text'];
                    } elseif (($block['type'] ?? '') === 'thinking' && isset($block['thinking'])) {
                        $this->accumulatedReasoningContent .= $block['thinking'];
                    } elseif (($block['type'] ?? '') === 'tool_use') {
                        $index = $data['index'] ?? count($this->accumulatedToolCallData);
                        $this->accumulatedToolCallData[$index] = [
                            'id' => $block['id'] ?? '',
                            'name' => $block['name'] ?? '',
                            'input_json' => ''
                        ];
                    }
                } elseif ($type === 'content_block_delta') {
                    $delta = $data['delta'] ?? [];

                    if (($delta['type'] ?? '') === 'text_delta' && isset($delta['text'])) {
                        $this->accumulatedContent .= $delta['text'];
                    } elseif (($delta['type'] ?? '') === 'thinking_delta' && isset($delta['thinking'])) {
                        $this->accumulatedReasoningContent .= $delta['thinking'];
                    } elseif (($delta['type'] ?? '') === 'input_json_delta' && isset($delta['partial_json'])) {
                        $index = $data['index'] ?? 0;
                        $this->accumulatedToolCallData[$index]['input_json'] .= $delta['partial_json'];
                    }
                } elseif ($type === 'message_delta') {
                    $delta = $data['delta'] ?? [];

                    if (isset($delta['stop_reason'])) {
                        $this->accumulatedFinishReason = $delta['stop_reason'];

                        if (!empty($this->accumulatedToolCallData)) {
                            $this->accumulatedToolCalls = array_map(
                                fn(array $toolCallData) => new ToolCall(
                                    $toolCallData['id'] ?? '',
                                    $toolCallData['name'] ?? '',
                                    is_array(json_decode($toolCallData['input_json'], true))
                                    ? json_decode($toolCallData['input_json'], true)
                                    : []
                                ),
                                $this->accumulatedToolCallData
                            );
                        }
                    }
                } elseif ($type === 'ping') {
                    continue;
                }
            }
        } while ($untilEof && !$body->eof());
    }

    protected function finalizeStream(): void
    {
        $contentBlocks = [];

        if ($this->accumulatedReasoningContent !== '') {
            $contentBlocks[] = ['type' => 'thinking', 'thinking' => $this->accumulatedReasoningContent];
        }

        if ($this->accumulatedContent !== '') {
            $contentBlocks[] = ['type' => 'text', 'text' => $this->accumulatedContent];
        }

        foreach ($this->accumulatedToolCalls as $toolCall) {
            $contentBlocks[] = [
                'type' => 'tool_use',
                'id' => $toolCall->id,
                'name' => $toolCall->name,
                'input' => $toolCall->arguments
            ];
        }

        $responseData = [
            'content' => $contentBlocks
        ];

        if ($this->accumulatedFinishReason !== '') {
            $responseData['stop_reason'] = $this->accumulatedFinishReason;
        }

        $this->response = $responseData;
    }

    protected function parseStructuredOutput(string $content): ?array
    {
        $decoded = (bool) preg_match('/\`{3}json(.*)\`{3}?/m', $content, $match)
            ? json_decode(trim($match[1]), true)
            : null;

        if ($decoded) {
            return $decoded;
        }

        return (bool) preg_match('/(\{.*\})\S*$/m', $content, $match)
            ? json_decode(trim($match[1]), true)
            : json_decode(trim($content), true);
    }
}
