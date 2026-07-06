<?php

namespace Sentience\Ai\Tools;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionNamedType;
use Sentience\Ai\Schema\Schema;
use Sentience\Ai\Schema\Schemable;

class Tool implements ToolInterface
{
    public function __construct(
        protected Closure $closure,
        protected ?Schemable $schema = null
    ) {
    }

    public function schema(string $name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'parameters' => $this->schema ?
                    $this->schema->schema()
                    : (function (): array{
                        $parameters = [];

                        foreach ((new ReflectionFunction($this->closure))->getParameters() as $parameter) {
                            $reflectionType = $parameter->getType();

                            if (!($reflectionType instanceof ReflectionNamedType)) {
                                throw new InvalidArgumentException('schemas only support named types');
                            }

                            if (!$reflectionType->isBuiltin()) {
                                throw new InvalidArgumentException('schemas only support built in types');
                            }

                            $required = !$parameter->isOptional() || $parameter->isDefaultValueAvailable();

                            $type = match ($reflectionType->getName()) {
                                'bool' => Schema::bool($required),
                                'int' => Schema::int($required),
                                'float' => Schema::float($required),
                                'string' => Schema::string($required),
                                'array' => Schema::array(required: $required),
                                default => throw new InvalidArgumentException('automatically generated schema does not support objects')
                            };

                            if ($reflectionType->allowsNull()) {
                                $type->nullable();
                            }

                            $parameters[$parameter->getName()] = $type;
                        }

                        return Schema::object($parameters)->schema();
                    })()
            ],
        ];
    }

    public function execute(array $arguments): string
    {
        return (string) ($this->closure)(...$arguments);
    }
}
