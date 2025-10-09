<?php

namespace Src\Payloads;

use JsonSerializable;
use Sentience\Mapper\Attributes\MapScalar;
use Sentience\Mapper\Traits\JsonSerializesMapKeys;

class TestPayloadNestedObject implements JsonSerializable
{
    use JsonSerializesMapKeys;

    #[MapScalar('nested_id')]
    public int $nestedId;

    #[MapScalar('nested_name')]
    public string $nestedName;
}
