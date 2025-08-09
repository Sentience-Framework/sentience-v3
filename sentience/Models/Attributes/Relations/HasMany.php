<?php

declare(strict_types=1);

namespace Sentience\Models\Attributes\Relations;

use Attribute;

#[Attribute]
class HasMany extends Relation
{
    public const string M_TO_R_JOIN_REGEX_PATTERN = '/(.+)\-\<(.+)/';
}
