<?php

namespace Modules\Interfaces;

interface Dto
{
    public static function fromAssoc(array $assoc): static;
}
