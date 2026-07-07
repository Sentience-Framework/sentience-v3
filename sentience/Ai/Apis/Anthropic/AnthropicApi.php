<?php

namespace Sentience\Ai\Apis\Anthropic;

use GuzzleHttp\Client;
use Sentience\Ai\Apis\ApiAbstract;
use Sentience\Ai\Apis\ToolCall;
use Sentience\Ai\Messages\AssistantMessage;
use Sentience\Ai\Messages\Message;
use Sentience\Ai\Messages\StructuredOutputMessage;
use Sentience\Ai\Messages\ToolMessage;
use Sentience\Ai\Schema\Schemable;

class AnthropicApi extends ApiAbstract
{
    protected Client $client;

    public function __construct(string $baseUri, string $apiKey)
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
            'headers' => [
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ],
        ]);
    }

    public function prompt(
        string $model,
        array $messages,
        array $tools,
        int $maxTokens,
        ?Schemable $structuredOutput
    ): AnthropicResponse {
        if ($structuredOutput) {
            $hasStructuredOutputMessage = false;

            foreach ($messages as $message) {
                if ($message instanceof StructuredOutputMessage) {
                    $hasStructuredOutputMessage = true;

                    break;
                }
            }

            if (!$hasStructuredOutputMessage) {
                array_unshift($messages, new StructuredOutputMessage(
                    'The final response to the user should be in minified JSON. '
                    . 'Follow the JSON standard (ISO/IEC 21778, ECMA-404, https://www.json.org/). '
                    . 'Strictly adhere to the following schema: '
                    . json_encode($structuredOutput->schema())
                ));
            }
        }

        $systemPrompt = '';
        $transformedMessages = [];

        foreach ($messages as $message) {
            if ($message instanceof Message && $message->role->value === 'system') {
                $systemPrompt .= implode("\n", $message->content);
                continue;
            }

            if ($message instanceof AssistantMessage) {
                $transformedMessages[] = $this->buildAssistantContent($message);
                continue;
            }

            if ($message instanceof ToolMessage) {
                $transformedMessages[] = $this->buildToolResultContent($message);
                continue;
            }

            if ($message instanceof Message) {
                $role = $message->role->value;
                $transformedMessages[] = [
                    'role' => $role,
                    'content' => $this->buildContent($message->content),
                ];
            }
        }

        $response = $this->client->post(
            '/v1/messages',
            [
                'json' => array_filter([
                    'model' => $model,
                    'messages' => $transformedMessages,
                    'system' => $systemPrompt,
                    'tools' => count($tools) > 0 ? $this->buildToolsSchema($tools) : null,
                    'max_tokens' => $maxTokens,
                ], fn($v) => $v !== null)
            ]
        );

        return new AnthropicResponse($response);
    }

    protected function buildAssistantContent(AssistantMessage $message): array
    {
        $contentBlocks = [];

        if ($message->content !== '') {
            $contentBlocks[] = [
                'type' => 'text',
                'text' => $message->content,
            ];
        }

        foreach ($message->toolCalls as $toolCall) {
            $contentBlocks[] = [
                'type' => 'tool_use',
                'id' => $toolCall->id,
                'name' => $toolCall->name,
                'input' => count($toolCall->arguments) > 0 ? $toolCall->arguments : [],
            ];
        }

        return [
            'role' => 'assistant',
            'content' => $contentBlocks,
        ];
    }

    protected function buildToolResultContent(ToolMessage $message): array
    {
        return [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'tool_result',
                    'tool_use_id' => $message->id,
                    'content' => $message->content,
                ],
            ],
        ];
    }

    protected function buildContent(array $content): array|string
    {
        if (count($content) === 1 && is_string($content[0])) {
            return $content[0];
        }

        $blocks = [];

        foreach ($content as $input) {
            if (is_string($input)) {
                $blocks[] = [
                    'type' => 'text',
                    'text' => $input,
                ];
            }
        }

        return count($blocks) === 1 ? $blocks[0]['text'] : $blocks;
    }

    protected function buildToolsSchema(array $tools): array
    {
        $schema = [];

        foreach ($tools as $name => $toolInterface) {
            $schema[] = [
                'name' => $name,
                'description' => '',
                'input_schema' => $toolInterface->schema(),
            ];
        }

        return $schema;
    }
}
