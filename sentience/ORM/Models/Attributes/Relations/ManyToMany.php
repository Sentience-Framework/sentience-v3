<?php

namespace Sentience\ORM\Models\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToMany extends Relation
{
    public const string M_TO_R_JOIN_REGEX_PATTERN = '/(.+)\-\<(.+)\:(.+)\>\-(.+)/';
    public const bool TO_MANY = true;

    public function __construct(string $model, string $mToRJoin, public string $pivot)
    {
        parent::__construct($model, $mToRJoin);
    }

    public function getPivotModelProperty(): string
    {
        return $this->parseMToRJoin()[1];
    }

    public function getPivotRelationProperty(): string
    {
        return $this->parseMToRJoin()[2];
    }
}
