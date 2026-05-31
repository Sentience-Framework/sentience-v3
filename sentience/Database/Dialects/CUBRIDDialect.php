<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\DriverInterface;
use Sentience\Database\Queries\Enums\TypeEnum;

class CUBRIDDialect extends SQLDialect
{
    public function __construct(DriverInterface $driver, int|string $version, array $options)
    {
        if (is_int($version)) {
            parent::__construct($driver, $version, $options);

            return;
        }

        preg_match('/CUBRID\s*(\d+(\.\d+)?)/i', $version, $match);

        parent::__construct($driver, $match[1] ?? $version, $options);
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::FLOAT => $size > 32 ? 'DOUBLE PRECISION' : 'FLOAT',
            TypeEnum::STRING => $size > 255 ? 'LONG VARCHAR' : sprintf('VARCHAR(%d)', $size ?? 255),
            default => parent::type($type, $size)
        };
    }
}
