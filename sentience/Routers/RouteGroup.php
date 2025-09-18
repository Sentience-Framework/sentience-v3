<?php

namespace Sentience\Routers;

class RouteGroup
{
    public string $route;
    public array $routes = [];
    public array $middleware = [];

    public static function route(string $route, array $middleware = []): static
    {
        return new static($route, $middleware);
    }

    public function __construct(string $route, array $middleware = [])
    {
        $this->setRoute($route);
        $this->setMiddleware($middleware);
    }

    public function setRoute(string $route): static
    {
        $this->route = trim($route, '/');

        return $this;
    }

    public function setMiddleware(array $middleware): static
    {
        $this->middleware = $middleware;

        return $this;
    }

    public function bind(Route|RouteGroup $route): static
    {
        $this->routes[] = $route;

        return $this;
    }
}
