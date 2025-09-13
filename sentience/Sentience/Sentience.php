<?php

namespace Sentience\Sentience;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;
use Sentience\Abstracts\Controller;
use Sentience\Abstracts\Middleware;
use Sentience\Abstracts\Singleton;
use Sentience\Exceptions\CallbackException;
use Sentience\Exceptions\DeprecatedException;
use Sentience\Exceptions\FatalErrorException;
use Sentience\Exceptions\NoticeException;
use Sentience\Exceptions\ParseException;
use Sentience\Exceptions\WarningException;
use Sentience\Helpers\Log;
use Sentience\Helpers\Reflector;
use Sentience\Routers\CliRouter;
use Sentience\Routers\Command;
use Sentience\Routers\HttpRouter;
use Sentience\Routers\Route;
use Sentience\Routers\RouteGroup;

class Sentience extends Singleton
{
    protected CliRouter $cliRouter;
    protected HttpRouter $httpRouter;
    protected ?Closure $handleFatalError = null;
    protected ?Closure $handleThrowable = null;
    protected array $services = [];
    protected array $injectables = [];

    public function __construct()
    {
        $this->cliRouter = new CliRouter();
        $this->httpRouter = new HttpRouter();
    }

    public function bindCommand(Command $command): static
    {
        $this->cliRouter->bind($command);

        return $this;
    }

    public function bindRoute(Route|RouteGroup $route): static
    {
        $this->httpRouter->bind($route);

        return $this;
    }

    public function handleFatalError(callable $callback): static
    {
        $this->handleFatalError = Closure::fromCallable($callback);

        return $this;
    }

    public function handleThrowable(callable $callback): static
    {
        $this->handleThrowable = Closure::fromCallable($callback);

        return $this;
    }

    public function bindService(object $service): static
    {
        $this->services[] = $service;

        return $this;
    }

    public function bindInjectable(string $name, mixed $value): static
    {
        $this->injectables[$name] = $value;

        return $this;
    }

    public function execute(): void
    {
        error_reporting(0);

        register_shutdown_function(
            function (): void {
                $error = error_get_last();

                if (!$error) {
                    return;
                }

                $s = $error['type'];
                $m = $error['message'];
                $f = $error['file'];
                $l = $error['line'];

                $exception = new FatalErrorException($m, 0, $s, $f, $l);

                if ($this->handleFatalError ? ($this->handleFatalError)($exception) : false) {
                    return;
                }

                $this->handleException($exception);
            }
        );

        set_error_handler(
            function (int $s, string $m, string $f, int $l): bool {
                if (!env('ERRORS_CATCH_NON_FATAL', true)) {
                    return true;
                }

                return match ($s) {
                    E_NOTICE,
                    E_USER_NOTICE => throw new NoticeException($m, 0, $s, $f, $l),
                    E_WARNING,
                    E_CORE_WARNING,
                    E_COMPILE_WARNING,
                    E_USER_WARNING => throw new WarningException($m, 0, $s, $f, $l),
                    E_DEPRECATED,
                    E_USER_DEPRECATED => throw new DeprecatedException($m, 0, $s, $f, $l),
                    E_PARSE => throw new ParseException($m, 0, $s, $f, $l),
                    default => false
                };
            }
        );

        try {
            is_cli()
                ? $this->executeCli()
                : $this->executeHttp();
        } catch (Throwable $exception) {
            if ($this->handleThrowable ? ($this->handleThrowable)($exception) : false) {
                return;
            }

            $this->handleException($exception);
        }
    }

    protected function executeCli(): void
    {
        $argv = Argv::getInstance();

        [$command, $words, $flags] = $this->cliRouter->match($argv);

        if (!$command) {
            $this->cliNotFound($argv);

            return;
        }

        $this->executeCallback(callback: $command->callback, middleware: $command->middleware, words: $words, flags: $flags);
    }

    protected function executeHttp(): void
    {
        $request = Request::getInstance();

        [$route, $pathVars, $statusCode] = $this->httpRouter->match($request);

        if ($statusCode) {
            if ($statusCode == 404) {
                $this->httpNotFound($request);

                return;
            }

            if ($statusCode == 405) {
                $this->httpMethodNotAllowed($request);

                return;
            }
        }

        $this->executeCallback(callback: $route->callback, middleware: $route->middleware, request: $request, pathVars: $pathVars);
    }

    protected function executeCallback(string|array|callable $callback, array $middleware, array $words = [], array $flags = [], ?Request $request = null, array $pathVars = []): void
    {
        $this->validateCallback($callback);

        $dependencyInjector = new DependencyInjector(
            [
                'words' => $words,
                'flags' => $flags,
                'request' => $request,
                ...$pathVars
            ],
            $this->services
        );

        $args = $this->executeMiddleware($dependencyInjector, $middleware);

        if (is_array($callback)) {
            [$callback, $args] = $this->constructCallbackClass($dependencyInjector, $callback, $args);
        }

        $callbackReflector = is_array($callback)
            ? new ReflectionMethod(...$callback)
            : new ReflectionFunction($callback);

        $filteredArgs = $dependencyInjector->getFunctionParameters($callbackReflector, $args);

        $callback(...$filteredArgs);
    }

    protected function executeMiddleware(DependencyInjector $dependencyInjector, array $middleware): ?array
    {
        $args = [];

        foreach ($middleware as $callback) {
            $this->validateCallback($callback);

            if (is_array($callback)) {
                [$callback, $args] = $this->constructCallbackClass($dependencyInjector, $callback, $args);
            }

            $args = $this->executeMiddlewareCallback($dependencyInjector, $callback, $args);
        }

        return $args;
    }

    protected function executeMiddlewareCallback(DependencyInjector $dependencyInjector, string|array|callable $callback, array $args): array
    {
        $callbackReflector = is_array($callback)
            ? new ReflectionMethod(...$callback)
            : new ReflectionFunction($callback);

        $filteredArgs = $dependencyInjector->getFunctionParameters($callbackReflector, $args);

        $middlewareArgs = $callback(...$filteredArgs);

        if (!is_array($middlewareArgs)) {
            return [...$args, ...$filteredArgs];
        }

        return [...$args, ...$filteredArgs, ...$middlewareArgs];
    }

    protected function validateCallback(string|array|callable $callback): void
    {
        if ($callback instanceof Closure) {
            return;
        }

        if (is_array($callback)) {
            [$class, $method] = $callback;

            if (!class_exists($class)) {
                throw new CallbackException('class %s does not exist"', $class);
            }

            if (!method_exists($class, $method)) {
                throw new CallbackException('class %s does not have method %s', $class, $method);
            }

            return;
        }

        if (!function_exists($callback)) {
            throw new CallbackException('function %s does not exist', $callback);
        }
    }

    protected function constructCallbackClass(DependencyInjector $dependencyInjector, array $callback, array $args): array
    {
        [$class, $method] = $callback;

        $reflectionClass = new ReflectionClass($class);

        $isSubclassOfController = $reflectionClass->isSubclassOf(Controller::class);
        $isSubclassOfMiddleware = $reflectionClass->isSubclassOf(Middleware::class);

        if (!in_array(true, [$isSubclassOfController, $isSubclassOfMiddleware])) {
            throw new CallbackException('class %s does not extend a supported base class', $class);
        }

        $constructorReflector = $reflectionClass->getConstructor();

        $filteredArgs = $constructorReflector ? $dependencyInjector->getFunctionParameters($constructorReflector, $args) : [];

        $callback = [new $class(...$filteredArgs), $method];

        $args = [...$args, ...$filteredArgs];

        return [$callback, $args];
    }

    protected function handleException(Throwable $exception): void
    {
        is_cli()
            ? $this->handleExceptionCli($exception)
            : $this->handleExceptionHttp($exception);
    }

    protected function handleExceptionCli(Throwable $exception): void
    {
        $lines = [
            sprintf('- Type    : %s', Reflector::getShortName($exception)),
            sprintf('- Message : %s', $exception->getMessage())
        ];

        if (env('ERRORS_STACK_TRACE', false)) {
            $lines[] = sprintf('- File    : %s', $exception->getFile());
            $lines[] = sprintf('- Line    : %d', $exception->getLine());

            $stackTrace = array_values(
                array_filter(
                    $exception->getTrace(),
                    fn(array $frame): bool => array_key_exists('file', $frame)
                )
            );

            if (count($stackTrace) > 0) {
                $lines[] = sprintf('- Trace   :');

                foreach ($stackTrace as $index => $frame) {
                    $file = $frame['file'];
                    $line = $frame['line'];

                    $function = array_key_exists('class', $frame)
                        ? $frame['class'] . $frame['type'] . $frame['function']
                        : $frame['function'];

                    $args = implode(
                        ', ',
                        array_map(
                            fn(mixed $arg): string => get_debug_type($arg),
                            $frame['args'] ?? []
                        )
                    );

                    $lines[] = sprintf(
                        '        %d : %s:%d %s(%s)',
                        $index + 1,
                        $file,
                        $line,
                        $function,
                        $args
                    );
                }
            }
        }

        Log::stderrBetweenEqualSigns('Exception', $lines);

        exit;
    }

    protected function handleExceptionHttp(Throwable $exception): void
    {
        $response = [
            'error' => [
                'type' => Reflector::getShortName($exception),
                'message' => $exception->getMessage()
            ]
        ];

        if (env('ERRORS_STACK_TRACE', false)) {
            $response['error']['file'] = $exception->getFile();
            $response['error']['line'] = $exception->getLine();

            $stackTrace = array_values(
                array_filter(
                    array_map(
                        function (array $frame): ?array {
                            if (!array_key_exists('file', $frame)) {
                                return null;
                            }

                            $file = $frame['file'];
                            $line = $frame['line'];

                            $function = array_key_exists('class', $frame)
                                ? $frame['class'] . $frame['type'] . $frame['function']
                                : $frame['function'];

                            $args = array_map(
                                fn(mixed $arg): string => get_debug_type($arg),
                                $frame['args'] ?? []
                            );

                            return [
                                'file' => $file,
                                'line' => $line,
                                'function' => $function,
                                'args' => $args
                            ];
                        },
                        $exception->getTrace()
                    )
                )
            );

            if (count($stackTrace) > 0) {
                $response['error']['trace'] = $stackTrace;
            }
        }

        Response::internalServerError($response);
    }

    protected function cliNotFound(Argv $argv): void
    {
        $lines = array_map(
            fn(Command $command): string => sprintf('- %s', $command->command),
            $this->cliRouter->commands
        );

        Log::stderrBetweenEqualSigns('Command not found', $lines);

        exit;
    }

    protected function httpNotFound(Request $request): void
    {
        Response::notFound([
            'error' => [
                'type' => 'HttpException',
                'message' => 'resource not found'
            ]
        ]);
    }

    protected function httpMethodNotAllowed(Request $request): void
    {
        Response::methodNotAllowed([
            'error' => [
                'type' => 'HttpException',
                'message' => 'method not allowed'
            ]
        ]);
    }
}
