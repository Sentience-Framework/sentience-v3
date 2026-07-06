<?php

namespace Sentience\Ai\Tools;

use Closure;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use stdClass;

class ClosureTool implements ToolInterface
{
    protected ReflectionFunction $reflectionFunction;

    public function __construct(protected Closure $closure)
    {
        $this->reflectionFunction = new ReflectionFunction($closure);
    }

    public function schema(string $name): array
    {
        $description = $this->extractDescription();
        $properties = [];
        $required = [];

        foreach ($this->reflectionFunction->getParameters() as $parameter) {
            $parameterName = $parameter->getName();
            $schema = $this->buildSchema($parameter);

            if (!$parameter->isOptional()) {
                $required[] = $parameterName;
            }

            $properties[$parameterName] = $schema;
        }

        if (array_is_list($properties)) {
            $properties = new stdClass();
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        $result = [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'parameters' => $schema,
            ],
        ];

        if ($description !== '') {
            $result['function']['description'] = $description;
        }

        return $result;
    }

    public function execute(array $arguments): string
    {
        return (string) ($this->closure)(...$arguments);
    }

    protected function buildSchema(ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();
        $schema = [];

        if ($type === null) {
            $schema['type'] = 'any';
        } elseif ($type instanceof ReflectionNamedType) {
            $schema = $this->buildNamedTypeSchema($type, $parameter);
        } elseif ($type instanceof ReflectionUnionType) {
            $schema = $this->buildUnionTypeSchema($type);
        }

        if ($parameter->isDefaultValueAvailable()) {
            $default = $parameter->getDefaultValue();
            if (is_bool($default)) {
                $schema['default'] = $default;
            } elseif (is_int($default) || is_float($default)) {
                $schema['default'] = $default;
            } elseif (is_string($default)) {
                $schema['default'] = $default;
            } elseif ($default === null) {
                $schema['nullable'] = true;
            }
        }

        return $schema;
    }

    protected function buildNamedTypeSchema(ReflectionNamedType $type, ReflectionParameter $parameter): array
    {
        $name = $type->getName();

        if ($type->isBuiltin()) {
            return match ($name) {
                'string' => ['type' => 'string'],
                'int', 'integer' => ['type' => 'integer'],
                'float', 'double' => ['type' => 'number'],
                'bool', 'boolean' => ['type' => 'boolean'],
                'array' => ['type' => 'array', 'items' => ['type' => 'any']],
                'object' => ['type' => 'object'],
                'mixed' => ['type' => 'any'],
                'null' => ['type' => 'null'],
                'callable' => ['type' => 'string'],
                'iterable' => ['type' => 'array', 'items' => ['type' => 'any']],
                default => ['type' => 'string'],
            };
        }

        if (enum_exists($name)) {
            $backedEnum = $this->getBackingType($name);
            return match ($backedEnum) {
                'int' => ['type' => 'integer', 'enum' => $this->getEnumValues($name)],
                'string' => ['type' => 'string', 'enum' => $this->getEnumValues($name)],
                default => ['type' => 'string', 'enum' => $this->getEnumValues($name)],
            };
        }

        return ['type' => 'object'];
    }

    protected function buildUnionTypeSchema(ReflectionUnionType $type): array
    {
        $types = [];
        $enums = [];
        $hasNull = false;

        foreach ($type->getTypes() as $unionType) {
            if (!$unionType instanceof ReflectionNamedType) {
                continue;
            }

            if ($unionType->getName() === 'null') {
                $hasNull = true;
                continue;
            }

            if ($unionType->isBuiltin()) {
                $types[] = match ($unionType->getName()) {
                    'string' => 'string',
                    'int', 'integer' => 'integer',
                    'float', 'double' => 'number',
                    'bool', 'boolean' => 'boolean',
                    'array' => 'array',
                    'object' => 'object',
                    'mixed' => 'any',
                    default => 'string',
                };
            } elseif (enum_exists($unionType->getName())) {
                $backedEnum = $this->getBackingType($unionType->getName());
                $values = $this->getEnumValues($unionType->getName());

                if ($hasNull) {
                    $types[] = 'integer';
                } else {
                    $enums[$unionType->getName()] = ['type' => $backedEnum, 'values' => $values];
                }
            }
        }

        $schema = [];
        if ($hasNull) {
            $schema['nullable'] = true;
        }

        if (count($enums) > 0) {
            foreach ($enums as $enumInfo) {
                $schema['type'] = $enumInfo['type'];
                $schema['enum'] = $enumInfo['values'];
            }
        } elseif (count($types) === 1) {
            return array_merge(['type' => $types[0]], $schema);
        } elseif (count($types) > 1) {
            $schema['type'] = 'any';
        }

        return $schema;
    }

    protected function getBackingType(string $enumName): string
    {
        $reflection = new \ReflectionEnum($enumName);
        $backing = $reflection->getBackingType();

        if ($backing instanceof ReflectionNamedType) {
            return $backing->getName();
        }

        return 'string';
    }

    protected function getEnumValues(string $enumName): array
    {
        $values = [];
        $cases = $enumName::cases();

        foreach ($cases as $case) {
            $backedEnum = $case;
            if (method_exists($backedEnum, 'value')) {
                $values[] = $backedEnum->value;
            } else {
                $values[] = $case->name;
            }
        }

        return $values;
    }

    protected function extractDescription(): string
    {
        $docComment = $this->reflectionFunction->getDocComment();

        if ($docComment === false) {
            return '';
        }

        $description = preg_replace('#^\s*\*\/?#', '', $docComment);
        $description = str_replace(['/*', '*/'], '', $description);
        $description = preg_replace('/\s*\n\s*\*/m', "\n", $description);
        $description = trim($description);

        if ($description === '') {
            return '';
        }

        return $description;
    }
}
