<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\DriverInterface;
use Sentience\Database\Queries\Enums\TypeEnum;

class InformixDialect extends SQLDialect
{
    public function __construct(DriverInterface $driver, int|string $version, array $options)
    {
        if (is_int($version)) {
            parent::__construct($driver, $version, $options);

            return;
        }

        preg_match('/Informix\s*(?:Dynamic\s*Server)\s*(\d+(\.\d+)?)/i', $version, $match);

        parent::__construct($driver, $match[1] ?? $version, $options);
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::DATETIME => 'DATETIME YEAR TO FRACTION',
            default => parent::type($type, $size)
        };
    }
}
