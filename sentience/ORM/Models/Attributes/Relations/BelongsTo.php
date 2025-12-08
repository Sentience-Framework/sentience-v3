<?php

namespace Sentience\ORM\Models\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo extends Relation
{
    public const string M_TO_R_JOIN_REGEX_PATTERN = '/(.+)\<\-(.+)/';
}
