<?php

namespace Sentience\Ai;

use Sentience\Ai\Attachments\AttachmentInterface;
use Sentience\Ai\Connectors\ConnectorInterface;

class Prompt
{
    protected ?string $systemPrompt = null;

    /**
     * @var AttachmentInterface[]
     */
    protected array $attachments = [];

    public function __construct(
        protected ConnectorInterface $connector,
        protected string $prompt
    ) {
    }

    public function withSystemPrompt(string $prompt): static
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    public function withDocumentFromUrl(string $url): static
    {
        return $this;
    }

    public function withDocumentFromPath(string $path): static
    {
        return $this;
    }

    public function withDocumentFromBase64(string $base64, ?string $filename): static
    {
        return $this;
    }
}
