<?php

namespace Sentience\Ai\Apis;

use Sentience\Ai\Schema\Schemable;

interface ApiInterface
{
    public function prompt(
        string $model,
        array $messages,
        array $tools,
        ?Schemable $structuredOutput
    ): ResponseInterface;
}
