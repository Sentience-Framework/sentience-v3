<?php

namespace Sentience\Ai;

use Closure;
use Sentience\Ai\Apis\ApiInterface;
use Sentience\Ai\Apis\ResponseInterface;
use Sentience\Ai\Attachments\Audio;
use Sentience\Ai\Attachments\Base64Attachment;
use Sentience\Ai\Attachments\Document;
use Sentience\Ai\Attachments\Image;
use Sentience\Ai\Attachments\UrlAttachment;
use Sentience\Ai\Attachments\Video;
use Sentience\Ai\Exceptions\ToolNotFoundException;
use Sentience\Ai\Messages\AssistantMessage;
use Sentience\Ai\Messages\Message;
use Sentience\Ai\Messages\Role;
use Sentience\Ai\Messages\ToolMessage;
use Sentience\Ai\Schema\Schema;
use Sentience\Ai\Schema\Schemable;
use Sentience\Ai\Tools\ClosureTool;
use Sentience\Ai\Tools\Tool;
use Sentience\Ai\Tools\ToolInterface;

class Prompt
{
    protected ?string $systemPrompt = null;
    protected array $previousMessages = [];
    protected array $tools = [];
    protected ?Schemable $structuredOutput = null;

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

    public function withTool(string $name, callable|ToolInterface $tool, ?Schemable $schema = null): static
    {
        $this->tools[$name] = is_callable($tool)
            ? new Tool(
                Closure::fromCallable($tool),
                $schema
            )
            : $tool;

        return $this;
    }

    public function withStructuredOutput(array $properties): static
    {
        $this->structuredOutput = Schema::object($properties);

        return $this;
    }

    public function execute(bool $loop = true): ResponseInterface
    {
        $messages = [];

        if ($this->systemPrompt) {
            $messages[] = new Message(
                Role::System,
                $this->systemPrompt,
            );
        }

        $messages = [
            ...$messages,
            ...$this->previousMessages
        ];

        $messages[] = new Message(
            Role::User,
            $this->prompt
        );

        while (true) {
            $response = $this->api->prompt(
                $this->model,
                $messages,
                $this->tools,
                $this->structuredOutput,
            );

            if (!$loop) {
                return $response;
            }

            $toolCalls = $response->getToolCalls();

            if (count($toolCalls) === 0) {
                return $response;
            }

            $messages[] = AssistantMessage::fromResponse($response);

            foreach ($toolCalls as $toolCall) {
                if (!array_key_exists($toolCall->name, $this->tools)) {
                    throw new ToolNotFoundException("tool $toolCall->name does not exist");
                }

                $tool = $this->tools[$toolCall->name];

                $result = $tool->execute($toolCall->arguments);

                $messages[] = new ToolMessage(
                    $toolCall->id,
                    $result
                );
            }
        }
    }
}
