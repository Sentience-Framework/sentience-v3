<?php

namespace Sentience\Ai\Apis;

use Sentience\Ai\StructuredOutput\StructuredOutputInterface;

interface ApiInterface
{
    public function prompt(
        string $model,
        array $messages,
        array $tools,
        ?StructuredOutputInterface $structuredOutput
    ): ResponseInterface;
}
