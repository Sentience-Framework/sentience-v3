<?php

namespace Sentience\Ai\Apis\OpenAI;

use GuzzleHttp\Client;
use Sentience\Ai\Apis\ApiAbstract;
use Sentience\Ai\Apis\ToolCall;
use Sentience\Ai\Messages\AssistantMessage;
use Sentience\Ai\Messages\Message;
use Sentience\Ai\Messages\Role;
use Sentience\Ai\Messages\ToolMessage;

class OpenAIApi extends ApiAbstract
{
    protected Client $client;

    public function __construct(string $baseUri, string $apiKey)
    {
        $this->client = new Client([
            'base_uri' => $baseUri,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
            ],
        ]);
    }

    public function prompt(
        string $model,
        array $messages,
        array $tools
    ): OpenAIResponse {
        $response = $this->client->post(
            '/v1/chat/completions',
            [
                'json' => [
                    'model' => $model,
                    'messages' => array_map(
                        function (Message $message): array {
                            if ($message instanceof AssistantMessage) {
                                return [
                                    'role' => $message->role->value,
                                    'content' => $message->content,
                                    'reasoning_content' => $message->reasoningContent,
                                    'tool_calls' => array_map(
                                        fn(ToolCall $toolCall): array => [
                                            'id' => $toolCall->toolCallId,
                                            'type' => 'function',
                                            'function' => [
                                                'name' => $toolCall->name,
                                                'arguments' => json_encode($toolCall->arguments)
                                            ]
                                        ],
                                        $message->toolCalls
                                    )
                                ];
                            }

                            if ($message instanceof ToolMessage) {
                                return [
                                    'role' => $message->role->value,
                                    'tool_call_id' => $message->toolCallId,
                                    'content' => $message->content,
                                ];
                            }

                            return [
                                'role' => $message->role->value,
                                'content' => $message->content,
                            ];
                        },
                        $messages
                    ),
                    'tools' => $this->buildToolsSchema($tools)
                ]
            ]
        );

        return new OpenAIResponse($response);
    }
}
