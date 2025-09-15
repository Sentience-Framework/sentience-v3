<?php

namespace Sentience\Interfaces;

interface DtoInterface
{
    public static function fromAssoc(array $assoc): static;
}
