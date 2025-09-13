<?php

namespace Modules\Interfaces;

interface Dto
{
    public static function fromArray(array $assoc): static;
}
