<?php

declare(strict_types=1);

namespace sentience\Sentience;

use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;
use sentience\Abstracts\Singleton;
use sentience\Exceptions\DependencyInjectionException;
use sentience\Helpers\Reflector;

class DependencyInjector
{
    public function __construct(protected array $injectables = [], protected ?object $service = null)
    {
    }

    public function getFunctionParameters(ReflectionFunctionAbstract $reflectionFunctionAbstract, array $injectables = []): array
    {
        $injectables = [
            ...$this->injectables,
            ...$injectables
        ];

        $serviceProperties = $this->service
            ? get_object_vars($this->service)
            : [];

        $serviceMethods = $this->service
            ? get_class_methods($this->service)
            : [];

        $functionParameters = $reflectionFunctionAbstract->getParameters();

        $parameters = [];

        foreach ($functionParameters as $functionParameter) {
            $name = $functionParameter->getName();

            if (array_key_exists($name, $injectables)) {
                $parameters[$name] = $injectables[$name];

                continue;
            }

            if (array_key_exists($name, $serviceProperties)) {
                $parameters[$name] = $serviceProperties[$name];

                continue;
            }

            if (in_array($name, $serviceMethods)) {
                $parameters[$name] = [$this->service, $name]();

                continue;
            }

            if ($functionParameter->isDefaultValueAvailable()) {
                $parameters[$name] = $functionParameter->getDefaultValue();

                continue;
            }

            if ($functionParameter->isVariadic()) {
                $parameters = [
                    ...$parameters,
                    ...array_filter(
                        $injectables,
                        fn(string $injectable): bool => !array_key_exists($injectable, $parameters),
                        ARRAY_FILTER_USE_KEY
                    )
                ];
                continue;
            }

            if ($this->isInjectable($functionParameter)) {
                $parameters[$name] = $this->createInjectableInstance($functionParameter);

                continue;
            }

            if ($functionParameter->allowsNull()) {
                $parameters[$name] = null;

                continue;
            }

            throw new DependencyInjectionException('%s is not a valid injectable parameter', $name);
        }

        return $parameters;
    }

    protected function isInjectable(ReflectionParameter $reflectionParameter): bool
    {
        $type = $this->getType($reflectionParameter);

        if (!$type) {
            return false;
        }

        if (!class_exists($type)) {
            return false;
        }

        return Reflector::isSubClassOf($type, Singleton::class);
    }

    protected function createInjectableInstance(ReflectionParameter $reflectionParameter): Singleton
    {
        $type = $this->getType($reflectionParameter);

        return $type::getInstance();
    }

    protected function getType(ReflectionParameter $reflectionParameter): ?string
    {
        $type = $reflectionParameter->getType();

        if (!($type instanceof ReflectionNamedType)) {
            return null;
        }

        return $type->getName();
    }
}
