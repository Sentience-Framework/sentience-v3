<?php

declare(strict_types=1);

namespace Sentience\Models\Attributes\Relations;

use Attribute;

#[Attribute]
class ManyToMany extends Relation
{
    public const M_TO_R_JOIN_REGEX_PATTERN = '/(.+)\-\<(.+)\:(.+)\>\-(.+)/';
}
