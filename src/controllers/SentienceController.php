<?php

namespace src\controllers;

use src\dotenv\DotEnv;
use src\exceptions\BuiltInWebServerException;
use src\exceptions\TerminalException;
use src\sentience\Stdio;
use src\utils\Filesystem;
use src\utils\Terminal;

class SentienceController extends Controller
{
    public function startServer(): void
    {
        $terminalWidth = Terminal::getWidth();

        if ($terminalWidth < 40) {
            throw new TerminalException('terminal width of %d is too small. minimum width of 40 required', $terminalWidth);
        }

        $dir = escapeshellarg(Filesystem::path(SENTIENCE_DIR, 'public'));
        $bin = escapeshellarg(defined(PHP_BINARY) ? PHP_BINARY : 'php');
        $host = env('SERVER_HOST', 'localhost');
        $port = env('SERVER_PORT', 8000);

        $command = sprintf('cd %s && %s -S %s:%d', $dir, $bin, $host, $port);

        if (PHP_OS_FAMILY == 'Windows') {
            passthru($command);

            return;
        }

        Terminal::stream(
            $command,
            function ($stdout, $stderr) use ($terminalWidth, &$startTime, &$endTime, &$path): void {
                if (empty($stderr)) {
                    return;
                }

                $stderr = str_ends_with($stderr, PHP_EOL)
                    ? substr($stderr, 0, -1)
                    : $stderr;

                $lines = explode(PHP_EOL, $stderr);

                foreach ($lines as $line) {
                    if (preg_match('/\(reason:\s*(.*?)\)/', $line, $matches)) {
                        throw new BuiltInWebServerException($matches[1]);
                    }

                    if (preg_match('/^\[.*?\]\sPHP/', $line)) {
                        $equalSigns = ($terminalWidth - 28) / 2 - 1;

                        Stdio::printFLn(
                            '%s Sentience development server %s',
                            str_repeat('=', ceil($equalSigns)),
                            str_repeat('=', floor($equalSigns))
                        );

                        continue;
                    }

                    if (preg_match('/^\[.*?\]\s.*\:\d+\s(\w+)/', $line, $matches)) {
                        $status = $matches[1];

                        if ($status == 'Accepted') {
                            $startTime = microtime(true);

                            continue;
                        }

                        if ($status == 'Closing') {
                            $endTime = microtime(true);

                            Stdio::errorFLn(
                                '%s (%.2f ms) %s',
                                date('Y-m-d H:i:s'),
                                ($endTime - $startTime) * 1000,
                                $path
                            );

                            continue;
                        }

                        continue;
                    }

                    if (preg_match('/^\[.*?\]\s.*\:\d+\s\[\d+\]\:\s\w+\s(.*)/', $line, $matches)) {
                        $path = $matches[1];

                        continue;
                    }

                    Stdio::errorLn($line);
                }

                return;
            },
            0
        );
    }

    public function fixDotEnv(array $words, array $flags): void
    {
        $dotEnv = $flags['dot-env'] ?? $words[0] ?? '.env';
        $dotEnvExample = $flags['dot-env-example'] ?? $words[1] ?? '.env.example';

        $dotEnvFilepath = Filesystem::path(SENTIENCE_DIR, $dotEnv);
        $dotEnvExampleFilepath = Filesystem::path(SENTIENCE_DIR, $dotEnvExample);

        $dotEnvVariables = DotEnv::parseFileRaw($dotEnvFilepath);
        $dotEnvExampleVariables = DotEnv::parseFileRaw($dotEnvExampleFilepath);

        $missingVariables = [];

        foreach ($dotEnvExampleVariables as $key => $value) {
            if (array_key_exists($key, $dotEnvVariables)) {
                continue;
            }

            $missingVariables[$key] = $value;
        }

        if (count($missingVariables) == 0) {
            Stdio::printFLn(
                '%s is up to date',
                $dotEnv
            );

            return;
        }

        $dotEnvFileContents = file_get_contents($dotEnvFilepath);

        $lines = preg_split('/[\r\n|\n|\r]/', $dotEnvFileContents);

        if (!empty(end($lines))) {
            $lines[] = '';
        }

        $lines[] = sprintf(
            '# imported %s variables from %s on %s',
            count($missingVariables),
            $dotEnvExample,
            date('Y-m-d H:i:s')
        );

        foreach ($missingVariables as $key => $value) {
            $lines[] = sprintf(
                '%s=%s',
                $key,
                $value
            );
        }

        $lines[] = '';

        $modifiedDotEnvFileContents = implode(PHP_EOL, $lines);

        file_put_contents($dotEnvFilepath, $modifiedDotEnvFileContents);

        Stdio::printFLn('Added %d variables to %s', count($missingVariables), $dotEnv);
    }
}
