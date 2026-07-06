<?php

namespace Sentience\Ai\Apis\OpenAI;

use GuzzleHttp\Client;
use Sentience\Ai\Apis\ApiAbstract;
use Sentience\Ai\Apis\ToolCall;
use Sentience\Ai\Messages\AssistantMessage;
use Sentience\Ai\Messages\Message;
use Sentience\Ai\Messages\Role;
use Sentience\Ai\Messages\StructuredOutputMessage;
use Sentience\Ai\Messages\ToolMessage;
use Sentience\Ai\StructuredOutput\StructuredOutputInterface;

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
        array $tools,
        ?StructuredOutputInterface $structuredOutput
    ): OpenAIResponse {
        if ($structuredOutput) {
            $hasStructuredOutputMessage = false;

            foreach ($messages as $message) {
                if ($message instanceof StructuredOutputMessage) {
                    $hasStructuredOutputMessage = true;

                    break;
                }
            }

            if (!$hasStructuredOutputMessage) {
                $messages = [
                    new StructuredOutputMessage('The final response to the user should be in minified JSON. Adhere to the JSON standard from https://www.json.org/. Strictly adhere to the following schema: ' . json_encode($structuredOutput->schema())),
                    ...$messages
                ];
            }

        }

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
                                                'arguments' => count($toolCall->arguments) > 0
                                                    ? json_encode($toolCall->arguments)
                                                    : '{}'
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
