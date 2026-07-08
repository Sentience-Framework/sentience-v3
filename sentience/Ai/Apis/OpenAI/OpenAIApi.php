<?php

namespace Sentience\Ai\Apis\OpenAI;

use GuzzleHttp\Client;
use Sentience\Ai\Apis\ApiAbstract;
use Sentience\Ai\Apis\ToolCall;
use Sentience\Ai\Attachments\Base64Attachment;
use Sentience\Ai\Attachments\UrlAttachment;
use Sentience\Ai\Messages\AssistantMessage;
use Sentience\Ai\Messages\MessageInterface;
use Sentience\Ai\Messages\SystemMessage;
use Sentience\Ai\Messages\ToolMessage;
use Sentience\Ai\Messages\UserMessage;
use Sentience\Ai\Schema\Schemable;
use stdClass;

class OpenAIApi extends ApiAbstract
{
    protected Client $client;

    public function __construct(string $baseUri, string $apiKey)
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
            'headers' => ['Authorization' => "Bearer $apiKey"],
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
    ): OpenAIResponse {
        $messages = [];

        if ($systemPrompt) {
            $messages[] = new SystemMessage($systemPrompt);
        }

        if ($structuredOutput) {
            $messages[] = $this->buildStructuredOutputMessage($structuredOutput);
        }

        array_push($messages, ...$previousMessages);

        $messages[] = new UserMessage($prompt);

        $response = $this->client->post(
            '/v1/chat/completions',
            [
                'json' => [
                    'model' => $model,
                    'messages' => array_map(
                        fn(MessageInterface $message) => $this->buildMessage($message),
                        $messages
                    ),
                    'tools' => $this->buildToolsSchema($tools),
                    'max_tokens' => $maxTokens
                ]
            ]
        );

        return new OpenAIResponse($response, (bool) $structuredOutput);
    }

    protected function buildMessage(MessageInterface $message): array
    {
        return match (true) {
            $message instanceof SystemMessage => $this->buildSystemMessage($message),
            $message instanceof UserMessage => $this->buildUserMessage($message),
            $message instanceof AssistantMessage => $this->buildAssistentMessage($message),
            $message instanceof ToolMessage => $this->buildToolMessage($message)
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
            'role' => $message->role->value,
            'content' => $this->buildContent($message->content),
        ];
    }

    protected function buildAssistentMessage(AssistantMessage $message): array
    {
        return [
            'role' => $message->role->value,
            'content' => $message->content,
            'reasoning_content' => $message->reasoningContent,
            'tool_calls' => array_map(
                fn(ToolCall $toolCall): array => [
                    'id' => $toolCall->id,
                    'type' => 'function',
                    'function' => [
                        'name' => $toolCall->name,
                        'arguments' => json_encode(
                            count($toolCall->arguments) > 0
                            ? $toolCall->arguments
                            : new stdClass()
                        )
                    ]
                ],
                $message->toolCalls
            )
        ];
    }

    protected function buildToolMessage(ToolMessage $message): array
    {
        return [
            'role' => $message->role->value,
            'tool_call_id' => $message->id,
            'content' => $message->content,
        ];
    }

    protected function buildContent(array $content): string|array
    {
        if (count($content) === 1 && is_string($content[0])) {
            return $content[0];
        }

        $inputs = [];

        foreach ($content as $input) {
            if (is_string($input)) {
                $inputs[] = [
                    'type' => 'input_text',
                    'text' => $input
                ];

                continue;
            }

            $inputs[] = match (true) {
                $input instanceof Base64Attachment => [
                    'type' => 'input_file',
                    'filename' => $input->filename,
                    'file_data' => $input->base64
                ],
                $input instanceof UrlAttachment => [
                    'type' => 'input_file',
                    'file_url' => $input->url,
                ]
            };
        }

        return $inputs;
    }

    protected function buildToolsSchema(array $tools): array
    {
        $schema = [];

        foreach ($tools as $name => $toolInterface) {
            $schema[] = [
                'type' => 'function',
                'function' => [
                    'name' => $toolInterface->name(),
                    'description' => $toolInterface->description(),
                    'parameters' => $toolInterface->schema($name)
                ]
            ];
        }

        return $schema;
    }
}
