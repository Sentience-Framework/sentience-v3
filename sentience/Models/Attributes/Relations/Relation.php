<?php

namespace Sentience\Models\Attributes\Relations;

use Attribute;
use Sentience\Exceptions\RelationException;

#[Attribute(Attribute::TARGET_PROPERTY)]
abstract class Relation
{
    public const string M_TO_R_JOIN_REGEX_PATTERN = '//';

    protected function parseMToRJoin(string $mToRJoin): array
    {
        $isMatch = preg_match(static::M_TO_R_JOIN_REGEX_PATTERN, $mToRJoin, $matches);

        if (!$isMatch) {
            throw new RelationException('%s does not match model to relation join %s', $mToRJoin, static::M_TO_R_JOIN_REGEX_PATTERN);
        }

        return \array_slice($matches, 1);
    }
}
