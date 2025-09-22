<?php

namespace Sentience\Sentience;

use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;
use Sentience\Abstracts\Singleton;
use Sentience\Exceptions\DependencyInjectionException;

class DependencyInjector
{
    public function __construct(
        protected array $injectables = [],
        protected array $services = []
    ) {
    }

    public function bindInjectable(string $name, mixed $value): static
    {
        $this->injectables[$name] = $value;

        return $this;
    }

    public function bindService(object $service): static
    {
        $this->services[] = $service;

        return $this;
    }

    public function getFunctionParameters(ReflectionFunctionAbstract $reflectionFunctionAbstract, array $injectables = []): array
    {
        $injectables = [
            ...$this->injectables,
            ...$injectables
        ];

        $serviceProperties = [];
        $serviceMethods = [];

        foreach ($this->services as $service) {
            $properties = get_object_vars($service);

            $methods = get_class_methods($service);

            foreach ($properties as $property) {
                $serviceProperties[$property] = [$service, $property];
            }

            foreach ($methods as $method) {
                $serviceMethods[$method] = [$service, $method];
            }
        }

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

            if (array_key_exists($name, $serviceMethods)) {
                $parameters[$name] = $serviceMethods[$name]();

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
                        fn (string $injectable): bool => !array_key_exists($injectable, $parameters),
                        ARRAY_FILTER_USE_KEY
                    )
                ];
                continue;
            }

            if ($this->isSingleton($functionParameter)) {
                $parameters[$name] = $this->getSingletonInstance($functionParameter);

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

    protected function isSingleton(ReflectionParameter $reflectionParameter): bool
    {
        $type = $this->getType($reflectionParameter);

        if (!$type) {
            return false;
        }

        if (!class_exists($type)) {
            return false;
        }

        return is_subclass_of($type, Singleton::class);
    }

    protected function getSingletonInstance(ReflectionParameter $reflectionParameter): Singleton
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
