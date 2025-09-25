<?php

namespace Sentience\DataLayer\Models\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasMany extends Relation
{
    public const string M_TO_R_JOIN_REGEX_PATTERN = '/(.+)\-\<(.+)/';
}
