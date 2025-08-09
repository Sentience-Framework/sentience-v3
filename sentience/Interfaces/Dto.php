<?php

declare(strict_types=1);

namespace Sentience\Interfaces;

interface Dto
{
    public static function fromArray(array $assoc): static;
}
