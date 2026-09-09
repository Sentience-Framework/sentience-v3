<?php

namespace Sentience\ORM\Models\Attributes\Columns;

use Attribute;
use Sentience\ORM\Models\Exceptions\CastException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Cast
{
    public function __construct(public string|array $encode, public string|array $decode)
    {
        foreach (['encode' => $encode, 'decode' => $decode] as $name => $callable) {
            if (is_callable($callable)) {
                continue;
            }

            throw new CastException('cast %s is not callable', $name);
        }
    }
}
