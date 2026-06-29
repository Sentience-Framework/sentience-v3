<?php

namespace Sentience\Ai\Apis\OpenAI;

use OpenAI;
use OpenAI\Client;
use Sentience\Ai\Apis\ApiInterface;
use Sentience\Ai\Messages\Role;

class OpenAIApi implements ApiInterface
{
    protected Client $client;

    public function __construct(string $baseUri, string $apiKey)
    {
        $this->client = OpenAI::factory()
            ->withBaseUri($baseUri)
            ->withApiKey($apiKey)
            ->withHttpClient($httpClient = new \GuzzleHttp\Client([]))
            ->withStreamHandler(fn(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface => $httpClient->send($request, ['stream' => true]))
            ->make();
    }

    public function prompt(
        string $model,
        string $prompt,
        ?string $systemPrompt,
        array $previousMessages,
        array $tools
    ): OpenAIResponse {
        $messages = [];

        foreach ($previousMessages as $previousMessage) {
            $messages[] = [
                'role' => $previousMessage->role->value,
                'content' => $previousMessage->content,
            ];
        }

        if ($systemPrompt) {
            $messages[] = [
                'role' => Role::System->value,
                'content' => $systemPrompt,
            ];
        }

        $messages[] = [
            'role' => Role::User->value,
            'content' => $prompt,
        ];

        $createResponse = $this->client->completions()->create([
            'model' => $model,
            'messages' => $messages,
            'tools' => $tools
        ]);

        return new OpenAIResponse($createResponse);
    }
}
