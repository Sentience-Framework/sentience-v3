<?php

namespace Sentience\Ai;

use Sentience\Ai\Apis\ApiInterface;
use Sentience\Ai\Apis\ResponseInterface;
use Sentience\Ai\Attachments\Audio;
use Sentience\Ai\Attachments\Document;
use Sentience\Ai\Attachments\Image;
use Sentience\Ai\Attachments\Video;
use Sentience\Ai\Tools\ToolInterface;

class Prompt
{
    protected ?string $systemPrompt = null;
    protected array $previousMessages = [];
    protected array $attachments = [];
    protected array $tools = [];

    public function __construct(
        protected ApiInterface $api,
        protected string $model,
        protected string $prompt
    ) {
    }

    public function withSystemPrompt(string $prompt): static
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    public function withPreviousMessages(array $messages): static
    {
        $this->previousMessages = $messages;

        return $this;
    }

    public function withDocument(Document $document): static
    {
        $this->attachments[] = $document;

        return $this;
    }

    public function withAudio(Audio $audio): static
    {
        $this->attachments[] = $audio;

        return $this;
    }

    public function withImage(Image $image): static
    {
        $this->attachments[] = $image;

        return $this;
    }

    public function withVideo(Video $video): static
    {
        $this->attachments[] = $video;

        return $this;
    }

    public function withTool(string $name, callable|ToolInterface $tool): static
    {
        $this->tools[$name] = $tool;

        return $this;
    }

    public function execute(): ResponseInterface
    {
        return $this->api->prompt(
            $this->model,
            $this->prompt,
            $this->systemPrompt,
            $this->previousMessages,
            $this->tools
        );
    }
}
