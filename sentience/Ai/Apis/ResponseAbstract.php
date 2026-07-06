<?php

namespace Sentience\Ai\Apis;

abstract class ResponseAbstract implements ResponseInterface
{
    public function getStructuredOutput(): ?array
    {
        $content = $this->getContent();

        return (bool) preg_match('/\`{3}json(.*)\`{3}?/', $content, $match)
            ? json_decode(trim($match[1]), true)
            : json_decode(trim($content), true);
    }
}
