<?php

declare(strict_types=1);

namespace sentience\Sentience;

use ReflectionFunctionAbstract;
use sentience\Exceptions\DependencyInjectionException;

class DependencyInjector
{
    public function __construct(protected array $injectables, protected ?object $service = null)
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

            if ($functionParameter->allowsNull()) {
                $parameters[$name] = null;

                continue;
            }

            throw new DependencyInjectionException('%s is not a valid injectable parameter', $name);
        }

        return $parameters;
    }
}
