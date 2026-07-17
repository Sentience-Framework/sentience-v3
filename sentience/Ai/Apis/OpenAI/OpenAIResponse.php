<?php

namespace Sentience\Ai\Apis\OpenAI;

use GuzzleHttp\Psr7\Response;
use Sentience\Ai\Apis\ResponseAbstract;
use Sentience\Ai\Apis\ChunkSize;
use Sentience\Ai\Apis\ToolCall;

class OpenAIResponse extends ResponseAbstract
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
            $content = [];
            foreach ($this->response['choices'] as $choice) {
                $content[] = $choice['message']['content'];
            }
            return implode(PHP_EOL, $content);
        }

        return $this->accumulatedContent;
    }

    public function getReasoningContent(): string
    {
        if (!empty($this->response)) {
            $reasoningContent = [];
            foreach ($this->response['choices'] as $choice) {
                $reasoningContent[] = $choice['message']['reasoning_content'];
            }
            return implode(PHP_EOL, $reasoningContent);
        }

        return $this->accumulatedReasoningContent;
    }

    public function getToolCalls(): array
    {
        if (!empty($this->response)) {
            $toolCalls = [];
            foreach ($this->response['choices'][0]['message']['tool_calls'] ?? [] as $toolCall) {
                $id = $toolCall['id'];
                $name = $toolCall['function']['name'];
                $arguments = json_decode($toolCall['function']['arguments'], true);
                $toolCalls[] = new ToolCall($id, $name, $arguments);
            }
            return $toolCalls;
        }

        return $this->accumulatedToolCalls;
    }

    public function getFinishReason(): string
    {
        if (!empty($this->response)) {
            $finishReasons = [];
            foreach ($this->response['choices'] as $choice) {
                $finishReasons[] = $choice['finish_reason'];
            }
            return implode(PHP_EOL, $finishReasons);
        }

        return $this->accumulatedFinishReason;
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

                if ($line === '' || str_starts_with($line, 'event:')) {
                    continue;
                }

                if (!str_starts_with($line, 'data: ')) {
                    continue;
                }

                $jsonString = substr($line, 6);

                if ($jsonString === '[DONE]') {
                    $this->finalizeStream();
                    $this->streamExhausted = true;
                    return;
                }

                $chunk = json_decode($jsonString, true);

                if (!isset($chunk['choices'][0]['delta'])) {
                    continue;
                }

                $delta = $chunk['choices'][0]['delta'];

                if (isset($delta['content']) && $delta['content'] !== '') {
                    $this->accumulatedContent .= $delta['content'];
                }

                if (isset($delta['reasoning_content']) && $delta['reasoning_content'] !== '') {
                    $this->accumulatedReasoningContent .= $delta['reasoning_content'];
                }

                if (isset($delta['tool_calls'])) {
                    foreach ($delta['tool_calls'] as $toolCallDelta) {
                        $index = $toolCallDelta['index'];

                        if (!isset($this->accumulatedToolCallData[$index])) {
                            $this->accumulatedToolCallData[$index] = [
                                'id' => $toolCallDelta['id'] ?? null,
                                'function' => ['name' => '', 'arguments' => '']
                            ];
                        }

                        if (isset($toolCallDelta['function']['name']) && $toolCallDelta['function']['name'] !== '') {
                            $this->accumulatedToolCallData[$index]['function']['name'] .= $toolCallDelta['function']['name'];
                        }

                        if (isset($toolCallDelta['function']['arguments'])) {
                            $this->accumulatedToolCallData[$index]['function']['arguments'] .= $toolCallDelta['function']['arguments'];
                        }
                    }
                }

                if (isset($chunk['choices'][0]['finish_reason']) && $chunk['choices'][0]['finish_reason'] !== null) {
                    $this->accumulatedFinishReason = $chunk['choices'][0]['finish_reason'];

                    if (!empty($this->accumulatedToolCallData)) {
                        $this->accumulatedToolCalls = array_map(
                            fn(array $toolCallData) => new ToolCall(
                                $toolCallData['id'] ?? '',
                                $toolCallData['function']['name'] ?? '',
                                is_array(json_decode($toolCallData['function']['arguments'], true))
                                ? json_decode($toolCallData['function']['arguments'], true)
                                : []
                            ),
                            $this->accumulatedToolCallData
                        );
                    }
                }
            }
        } while ($untilEof && !$body->eof());
    }

    protected function finalizeStream(): void
    {
        $this->response = [
            'choices' => [
                [
                    'message' => [
                        'content' => $this->accumulatedContent,
                        'reasoning_content' => $this->accumulatedReasoningContent,
                        'tool_calls' => array_map(
                            fn(ToolCall $toolCall) => [
                                'id' => $toolCall->id,
                                'type' => 'function',
                                'function' => [
                                    'name' => $toolCall->name,
                                    'arguments' => json_encode((object) $toolCall->arguments)
                                ]
                            ],
                            $this->accumulatedToolCalls
                        )
                    ],
                    'finish_reason' => $this->accumulatedFinishReason
                ]
            ]
        ];
    }
}
