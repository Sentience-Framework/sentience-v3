<?php

namespace Sentience\Sentience;

use ReflectionClass;
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

            if ($singleton = $this->isSingleton($functionParameter)) {
                $parameters[$name] = $singleton::getInstance();

                continue;
            }

            if ($class = $this->isConstructableClass($functionParameter)) {
                $parameters[$name] = $class;

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

    protected function isSingleton(ReflectionParameter $reflectionParameter): bool|string
    {
        $class = $this->getReflectionParameterClass($reflectionParameter);

        if (!$class) {
            return false;
        }

        return is_subclass_of($class, Singleton::class) ? $class : false;
    }

    protected function isConstructableClass(ReflectionParameter $reflectionParameter): bool|object
    {
        $class = $this->getReflectionParameterClass($reflectionParameter);

        if (!$class) {
            return false;
        }

        $reflectionClass = new ReflectionClass($class);

        $reflectionClassConstructor = $reflectionClass->getConstructor();

        $functionParameters = $reflectionClassConstructor
            ? $this->getFunctionParameters($reflectionClassConstructor)
            : [];

        return new $class(...$functionParameters);
    }

    protected function getReflectionParameterClass(ReflectionParameter $reflectionParameter): bool|string
    {
        $reflectionType = $reflectionParameter->getType();

        if (!($reflectionType instanceof ReflectionNamedType)) {
            return false;
        }

        $type = $reflectionType->getName();

        if (!$type) {
            return false;
        }

        if (!class_exists($type)) {
            return false;
        }

        return $type;
    }
}
