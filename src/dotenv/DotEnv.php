<?php

namespace src\dotenv;

use src\exceptions\DotEnvException;

class DotEnv
{
    protected string $filepath;
    protected ?string $exampleFilepath;

    public function __construct(string $filepath, ?string $exampleFilepath = null)
    {
        $this->filepath = $filepath;
        $this->exampleFilepath = $exampleFilepath;
    }

    public function loadEnv(bool $parseBooleans = false, bool $parseDirectoryArrays = false): void
    {
        $env = getenv();

        foreach ($env as $key => $value) {
            if ($parseBooleans && in_array($value, ['0', '1'])) {
                $_ENV[$key] = [
                    '0' => false,
                    '1' => true
                ][$value];

                continue;
            }

            if ($parseDirectoryArrays && str_contains($value, DIRECTORY_SEPARATOR) && str_contains($value, PATH_SEPARATOR)) {
                $_ENV[$key] = explode(':', $value);

                continue;
            }

            $_ENV[$key] = $value;
        }
    }

    public function loadFile(array $variables = []): void
    {
        $parsedVariables = $this->parseFile($variables);

        foreach ($parsedVariables as $key => $value) {
            $_ENV[$key] = $value;
        }
    }

    public function parseFile(array $variables = []): array
    {
        $rawVariables = $this->parseFileRaw();

        $parsedVariables = [
            ...$_ENV,
            ...$variables
        ];

        foreach ($rawVariables as $key => $value) {
            $parsedVariables[$key] = $this->parseVariable($value, $parsedVariables);
        }

        return $parsedVariables;
    }

    public function parseFileRaw(): array
    {
        if (!file_exists($this->filepath)) {
            $this->createFile($this->filepath, $this->exampleFilepath);
        }

        $fileContents = file_get_contents($this->filepath);

        return $this->parseDotEnvString($fileContents);
    }

    protected function createFile(string $filepath, ?string $exampleFilepath): void
    {
        (bool) $exampleFilepath
            ? copy($exampleFilepath, $filepath)
            : file_put_contents($filepath, '');
    }

    protected function parseDotEnvString(string $string): array
    {
        $isMatch = preg_match_all('/^(?!\#)\s*([A-Z0-9_]+)\s*=\s*(?|(\'.*?\')|(\".*?\")|(\`{3}[\s\S]*?\`{3})|([^#\r\n]*))\s*(?=[\r\n]|$|\#)/m', $string, $matches);

        if (!$isMatch) {
            throw new DotEnvException('parsing error');
        }

        $variables = [];

        foreach ($matches[0] as $index => $variable) {
            $key = $matches[1][$index];
            $value = $matches[2][$index];

            $variables[$key] = $value;
        }

        return $variables;
    }

    protected function parseVariable(string $value, array $parsedVariables): mixed
    {
        if (str_starts_with($value, '[')) {
            return $this->parseArrayValue($value, $parsedVariables);
        }

        if (in_array(substr($value, 0, 1), ['"', "'", '`'])) {
            return $this->parseQuotedValue($value, $parsedVariables);
        }

        if (preg_match('/^\-{1}?[0-9]+$/', $value)) {
            return $this->parseIntValue($value);
        }

        if (is_numeric($value)) {
            return $this->parseFloatValue($value);
        }

        if (preg_match('/^false|true$/', $value)) {
            return $this->parseBoolValue($value);
        }

        if ($value == 'null') {
            return $this->parseNullValue($value);
        }

        return $value;
    }

    protected function parseArrayValue(string $value, array $parsedVariables): array
    {
        $values = [];

        $isMatch = preg_match_all('/(\"(.*?)\")|(\'(.*?)\')|[\-\w.]+/', $value, $matches, PREG_UNMATCHED_AS_NULL);

        if (!$isMatch) {
            return $values;
        }

        return array_map(
            function (string $value) use ($parsedVariables): mixed {
                return $this->parseVariable($value, $parsedVariables);
            },
            $matches[0]
        );
    }

    protected function parseQuotedValue(string $value, array $parsedVariables): string
    {
        return match (substr($value, 0, 1)) {
            '"' => $this->parseTemplateValue($value, '"', $parsedVariables),
            "'" => $this->parseStringValue($value, "'"),
            '`' => $this->parseTemplateValue($value, '```', $parsedVariables)
        };
    }

    protected function parseTemplateValue(string $value, string $quote, array $parsedVariables): string
    {
        $string = $this->parseStringValue($value, $quote);

        return preg_replace_callback(
            '/\$\{(.[^\}]*)\}/',
            function (array $matches) use ($parsedVariables): mixed {
                [$original, $key] = $matches;

                if (key_exists($key, $parsedVariables)) {
                    return $parsedVariables[$key];
                }

                return $original;
            },
            $string
        );
    }

    protected function parseStringValue(string $value, string $quote): string
    {
        $quoteLength = strlen($quote);

        $valueWithoutQuotes = trim(
            substr(
                $value,
                $quoteLength,
                $quoteLength * -1
            ),
            "\r\n"
        );

        $quoteChar = substr($quote, 0, 1);

        return str_replace(
            sprintf('\\%s', $quoteChar),
            $quoteChar,
            $valueWithoutQuotes
        );
    }

    protected function parseFloatValue(string $value): float
    {
        return (float) $value;
    }

    protected function parseIntValue(string $value): int
    {
        return (int) $value;
    }

    protected function parseBoolValue(string $value): bool
    {
        return match ($value) {
            'false' => false,
            'true' => true
        };
    }

    protected function parseNullValue(string $value): mixed
    {
        return null;
    }
}
