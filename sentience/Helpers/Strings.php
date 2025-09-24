<?php

namespace Sentience\Helpers;

class Strings
{
    public static function escapeChars(string $string, array $chars, string $replacement = '\\\\$0', string $pattern = '/(?<!\\\\)(?:\\\\\\\\)*%s/'): string
    {
        foreach ($chars as $char) {
            $string = preg_replace(
                sprintf(
                    $pattern,
                    preg_quote((string) $char, '/')
                ),
                $replacement,
                (string) $string
            );
        }

        return $string;
    }

    public static function extractChars(string $string, array $chars): string
    {
        return preg_replace(
            sprintf(
                '/[^%s]/',
                preg_quote(
                    implode(
                        '',
                        $chars
                    ),
                    '/'
                )
            ),
            '',
            $string
        );
    }

    public static function singularize(string $word): string
    {
        $rules = [
            '/^.{1}ies$/i' => fn (): string => substr($word, 0, -1),
            '/ies$/i' => fn (): string => substr_replace($word, (preg_match('/[A-Z]{1}.{2}$/', $word) ? 'Y' : 'y'), -3),
            '/[a-z]es$/i' => fn (): string => substr($word, 0, -2),
            '/s{1}$/i' => fn (): string => substr($word, 0, -1)
        ];

        foreach ($rules as $pattern => $callback) {
            if (preg_match($pattern, $word)) {
                return $callback();
            }
        }

        return $word;
    }

    public static function pluralize(string $word): string
    {
        $rules = [
            '/[^aeiouy]y$/i' => fn (): string => substr($word, 0, -1) . (preg_match('/[A-Z]y$/', $word) ? 'IES' : 'ies'),
            '/[aeiou]y$/i' => fn (): string => "{$word}s",
            '/(s|x|z|ch|sh)$/i' => fn (): string => "{$word}s",
            '/[^aeiou]e$/i' => fn (): string => "{$word}s"
        ];

        foreach ($rules as $pattern => $callback) {
            if (preg_match($pattern, $word)) {
                return $callback();
            }
        }

        return "{$word}s";
    }

    public static function toSnakeCase(string $string): string
    {
        $string = preg_replace('/([a-z])([A-Z])/', '$1 $2', $string);
        $string = preg_replace('/[_\-\s]+/', '_', strtolower((string) $string));

        return $string;
    }

    public static function toCamelCase($string): string
    {
        $string = lcfirst(static::toPascalCase($string));

        return $string;
    }

    public static function toPascalCase($string): string
    {
        $string = preg_replace('/[-_\s]+/', ' ', (string) $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);

        return $string;
    }
}
