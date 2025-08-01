<?php

namespace sentience\Helpers;

class Strings
{
    public static function escapeChars(string $string, array $chars, string $replacement = '\\\$0', string $pattern = '/(?<!\\\\)(?:\\\\\\\\)*%s/'): string
    {
        foreach ($chars as $char) {
            $string = preg_replace(
                sprintf(
                    $pattern,
                    preg_quote($char, '/')
                ),
                $replacement,
                $string
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
}
