<?php

namespace Sentience\Interfaces;

interface Dto
{
    public static function fromAssoc(array $assoc): static;
}
