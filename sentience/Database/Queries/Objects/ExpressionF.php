<?php

namespace Sentience\Database\Queries\Objects;

use ArgumentCountError;

class ExpressionF extends Expression
{
    public const array INT_MODIFIERS = ['d', 'u', 'c', 'o', 'x', 'X', 'b'];
    public const array FLOAT_MODIFIERS = ['e', 'E', 'f', 'F', 'g', 'G', 'h', 'H'];
    public const array STRING_MODIFIERS = ['s'];

    public function __construct(
        string $format,
        array $values
    ) {
        $pattern = '/(?<!\%)\%(\.[0-9]{0,53})?(['
            . implode(
                '',
                [
                    ...static::INT_MODIFIERS,
                    ...static::FLOAT_MODIFIERS,
                    ...static::STRING_MODIFIERS
                ]
            )
            . '])/';

        $params = [];

        $index = 0;

        $sql = preg_replace_callback(
            $pattern,
            function (array $match) use ($values, &$params, &$index): string {
                if (!array_key_exists($index, $values)) {
                    throw new ArgumentCountError(
                        sprintf(
                            '%d arguments are required, %d given',
                            $index + 1,
                            count($values)
                        )
                    );
                }

                $value = $values[$index];

                $index++;

                $precision = $match[1];
                $type = $match[2];

                $params[] = match (true) {
                    is_null($value) => null,
                    in_array($type, static::INT_MODIFIERS) => (int) $value,
                    in_array($type, static::FLOAT_MODIFIERS) => !empty($precision)
                    ? round($value, (int) substr($precision, 1))
                    : (float) $value,
                    default => (string) $value
                };

                return '?';
            },
            $format
        );

        parent::__construct($sql, $params);
    }
}
