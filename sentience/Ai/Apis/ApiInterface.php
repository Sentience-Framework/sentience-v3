<?php

namespace Sentience\Ai\Apis;

interface ApiInterface
{
    public function prompt(
        string $model,
        array $messages,
        array $tools
    ): ResponseInterface;
}
