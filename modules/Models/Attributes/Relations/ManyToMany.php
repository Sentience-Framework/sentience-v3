<?php

declare(strict_types=1);

namespace Modules\Models\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToMany extends Relation
{
    public const string M_TO_R_JOIN_REGEX_PATTERN = '/(.+)\-\<(.+)\:(.+)\>\-(.+)/';
}
