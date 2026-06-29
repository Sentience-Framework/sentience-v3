<?php

namespace Sentience\Ai\Apis;

interface ApiInterface
{
    public function prompt(
        string $model,
        string $prompt,
        ?string $systemPrompt,
        array $previousMessages,
        array $tools
    ): ResponseInterface;
}
