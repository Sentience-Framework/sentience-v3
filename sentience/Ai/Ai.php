<?php

namespace Sentience\Ai;

use BackedEnum;
use Sentience\Ai\Apis\ApiInterface;
use Sentience\Ai\Apis\OpenAI\OpenAIApi;

class Ai
{
    protected ApiInterface $api;

    public static function connect(
        Api $api,
        string $baseUri,
        string $apiKey
    ): static {
        return new static(
            $api,
            $baseUri,
            $apiKey
        );
    }

    public function __construct(
        Api $api,
        string $baseUri,
        string $apiKey
    ) {
        $this->api = match ($api) {
            Api::OpenAI => new OpenAIApi($baseUri, $apiKey)
        };
    }

    public function prompt(string|BackedEnum $model, string $prompt): Prompt
    {
        return new Prompt(
            $this->api,
            $model instanceof BackedEnum ? $model->value : $model,
            $prompt
        );
    }
}
