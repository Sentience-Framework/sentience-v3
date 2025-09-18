<?php

namespace Sentience\Routers;

class Route
{
    public string $route;
    public mixed $callback;
    public array $methods = [];
    public array $middleware = [];

    public static function get(string $route, string|array|callable $callback, array $middleware = []): static
    {
        return new static($route, $callback, $middleware, ['GET']);
    }

    public static function post(string $route, string|array|callable $callback, array $middleware = []): static
    {
        return new static($route, $callback, $middleware, ['POST']);
    }

    public static function put(string $route, string|array|callable $callback, array $middleware = []): static
    {
        return new static($route, $callback, $middleware, ['PUT']);
    }

    public static function patch(string $route, string|array|callable $callback, array $middleware = []): static
    {
        return new static($route, $callback, $middleware, ['PATCH']);
    }

    public static function delete(string $route, string|array|callable $callback, array $middleware = []): static
    {
        return new static($route, $callback, $middleware, ['DELETE']);
    }

    public static function any(string $route, string|array|callable $callback, array $middleware = []): static
    {
        return new static($route, $callback, $middleware, ['*']);
    }

    public function __construct(string $route, string|array|callable $callback, array $middleware = [], array $methods = ['*'])
    {
        $this->setRoute($route);
        $this->setCallback($callback);
        $this->setMiddleware($middleware);
        $this->setMethods($methods);
    }

    public function setRoute(string $route): static
    {
        $this->route = trim($route, '/');

        return $this;
    }

    public function setCallback(string|array|callable $callback): static
    {
        $this->callback = $callback;

        return $this;
    }

    public function setMiddleware(array $middleware): static
    {
        $this->middleware = $middleware;

        return $this;
    }

    public function setMethods(array $methods): static
    {
        $this->methods = array_map(
            fn (string $method): string => strtoupper($method),
            $methods
        );

        return $this;
    }
}
