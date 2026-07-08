<?php

namespace Sentience\Ai\Apis\Anthropic;

use GuzzleHttp\Client;
use Sentience\Ai\Apis\ApiAbstract;
use Sentience\Ai\Apis\ToolCall;
use Sentience\Ai\Messages\AssistantMessage;
use Sentience\Ai\Messages\MessageInterface;
use Sentience\Ai\Messages\SystemMessage;
use Sentience\Ai\Messages\ToolMessage;
use Sentience\Ai\Messages\UserMessage;
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
        string $prompt,
        ?string $systemPrompt,
        array $tools,
        array $previousMessages,
        int $maxTokens,
        ?Schemable $structuredOutput
    ): AnthropicResponse {
        $messages = [];

        if ($structuredOutput) {
            $messages[] = $this->buildStructuredOutputMessage($structuredOutput);
        }

        array_push($messages, ...$previousMessages);

        $messages[] = new UserMessage($prompt);

        $response = $this->client->post(
            '/v1/messages',
            [
                'json' => [
                    'model' => $model,
                    'system' => $systemPrompt ?? '',
                    'messages' => array_map(
                        fn(MessageInterface $message) => $this->buildMessage($message),
                        $messages
                    ),
                    'tools' => $this->buildToolsSchema($tools),
                    'max_tokens' => $maxTokens,
                ],
            ]
        );

        return new AnthropicResponse($response, (bool) $structuredOutput);
    }

    protected function buildMessage(MessageInterface $message): array
    {
        return match (true) {
            $message instanceof SystemMessage => $this->buildSystemMessage($message),
            $message instanceof UserMessage => $this->buildUserMessage($message),
            $message instanceof AssistantMessage => $this->buildAssistantMessage($message),
            $message instanceof ToolMessage => $this->buildToolResultMessage($message)
        };
    }

    protected function buildSystemMessage(SystemMessage $message): array
    {
        return [
            'role' => $message->role->value,
            'content' => $message->content,
        ];
    }

    protected function buildUserMessage(UserMessage $message): array
    {
        return [
            'role' => 'user',
            'content' => $this->buildContent($message->content),
        ];
    }

    protected function buildAssistantMessage(AssistantMessage $message): array
    {
        $content = [];

        if (!empty($message->reasoningContent)) {
            $content[] = [
                'type' => 'thinking',
                'thinking' => $message->reasoningContent,
            ];
        }

        if (!empty($message->content)) {
            $content[] = [
                'type' => 'text',
                'text' => $message->content,
            ];
        }

        foreach ($message->toolCalls as $toolCall) {
            $content[] = [
                'type' => 'tool_use',
                'id' => $toolCall->id,
                'name' => $toolCall->name,
                'input' => $toolCall->arguments,
            ];
        }

        return [
            'role' => 'assistant',
            'content' => $content,
        ];
    }

    protected function buildToolResultMessage(ToolMessage $message): array
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

        $inputs = [];

        foreach ($content as $input) {
            if (is_string($input)) {
                $inputs[] = [
                    'type' => 'text',
                    'text' => $input
                ];
            }
        }

        return $inputs;
    }

    protected function buildToolsSchema(array $tools): array
    {
        $schema = [];

        foreach ($tools as $name => $toolInterface) {
            $schema[] = [
                'name' => $toolInterface->name(),
                'description' => $toolInterface->description(),
                'input_schema' => $toolInterface->schema($name),
            ];
        }

        return $schema;
    }
}
