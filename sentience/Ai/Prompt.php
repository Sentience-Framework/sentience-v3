<?php

namespace Sentience\Ai;

use Closure;
use Sentience\Ai\Apis\ApiInterface;
use Sentience\Ai\Apis\ResponseInterface;
use Sentience\Ai\Exceptions\ToolNotFoundException;
use Sentience\Ai\Messages\AssistantMessage;
use Sentience\Ai\Messages\Message;
use Sentience\Ai\Messages\Role;
use Sentience\Ai\Messages\ToolMessage;
use Sentience\Ai\Schema\Schema;
use Sentience\Ai\Schema\Schemable;
use Sentience\Ai\Schema\Types\ObjectType;
use Sentience\Ai\Tools\Tool;
use Sentience\Ai\Tools\ToolInterface;

class Prompt
{
    protected ?string $systemPrompt = null;
    protected array $previousMessages = [];
    protected array $tools = [];
    protected int $maxTokens = 1048578;
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

    public function withTool(
        string $name,
        callable|ToolInterface $tool,
        ?array $schema = null,
        ?string $description = null
    ): static {
        $this->tools[$name] = is_callable($tool)
            ? new Tool(
                $name,
                $description,
                Closure::fromCallable($tool),
                $schema !== null ? Schema::object($schema) : $schema
            )
            : $tool;

        return $this;
    }

    public function withMaxTokens(int $maxTokens): static
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function withStructuredOutput(ObjectType $schema): static
    {
        $this->structuredOutput = $schema;

        return $this;
    }

    public function execute(bool $loop = true): ResponseInterface
    {
        while (true) {
            $response = $this->api->prompt(
                $this->model,
                $this->prompt,
                $this->systemPrompt,
                $this->tools,
                $this->previousMessages,
                $this->maxTokens,
                $this->structuredOutput,
            );

            if (!$loop) {
                return $response;
            }

            $toolCalls = $response->getToolCalls();

            if (count($toolCalls) === 0) {
                return $response;
            }

            $this->previousMessages[] = AssistantMessage::fromResponse($response);

            foreach ($toolCalls as $toolCall) {
                if (!array_key_exists($toolCall->name, $this->tools)) {
                    throw new ToolNotFoundException("tool $toolCall->name does not exist");
                }

                $tool = $this->tools[$toolCall->name];

                $result = $tool->execute($toolCall->arguments);

                $this->previousMessages[] = new ToolMessage(
                    $toolCall->id,
                    $result
                );
            }
        }
    }
}
