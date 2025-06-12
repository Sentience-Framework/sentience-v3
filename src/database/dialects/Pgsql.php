<?php

namespace src\database\dialects;

use DateTime;
use src\database\queries\objects\Raw;

class Pgsql extends Sql implements DialectInterface
{
    public const REGEX_FUNCTION = '~';
    public const NOT_REGEX_FUNCTION = '!~';

    public function addOnConflict(string &$query, array &$params, null|string|array $conflict, ?array $conflictUpdates, ?string $primaryKey, array $insertValues): void
    {
        if (is_null($conflict)) {
            return;
        }

        $expression = is_string($conflict)
            ? sprintf('ON CONSTRAINT %s', $this->escapeIdentifier($conflict))
            : sprintf(
                '(%s)',
                implode(
                    ', ',
                    array_map(
                        function (string $column): string {
                            return $this->escapeIdentifier($column);
                        },
                        $conflict
                    )
                )
            );

        if (is_null($conflictUpdates)) {
            $query .= sprintf(' ON CONFLICT %s DO NOTHING', $expression);
            return;
        }

        $updates = !empty($conflictUpdates) ? $conflictUpdates : $insertValues;

        $query .= sprintf(
            ' ON CONFLICT %s DO UPDATE SET %s',
            $expression,
            implode(
                ', ',
                array_map(
                    function (mixed $value, string $key) use (&$params): string {
                        if ($value instanceof Raw) {
                            return sprintf(
                                '%s = %s',
                                $this->escapeIdentifier($key),
                                $value->expression
                            );
                        }

                        $params[] = $value;

                        return sprintf('%s = ?', $this->escapeIdentifier($key));
                    },
                    $updates,
                    array_keys($updates)
                )
            )
        );
    }

    public function castBool(bool $bool): mixed
    {
        return $bool ? 't' : 'f';
    }

    public function parseDateTime(?string $dateTimeString): ?DateTime
    {
        if (in_array(substr($dateTimeString, -3, 1), ['-', '+'])) {
            return parent::parseDateTime(sprintf('%s:00', $dateTimeString));
        }

        return parent::parseDateTime($dateTimeString);
    }

    public function phpTypeToColumnType(string $type, bool $isAutoIncrement, bool $isPrimaryKey, bool $inConstraint): string
    {
        if ($isAutoIncrement && $type == 'int') {
            return 'SERIAL';
        }

        return [
            'bool' => 'BOOL',
            'int' => 'INT8',
            'float' => 'FLOAT8',
            'string' => 'TEXT',
            'DateTime' => 'TIMESTAMP'
        ][$type];
    }
}
