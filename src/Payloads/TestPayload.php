<?php

namespace Src\Payloads;

use JsonSerializable;
use Sentience\Mapper\Attributes\MapArray;
use Sentience\Mapper\Attributes\MapObject;
use Sentience\Mapper\Attributes\MapScalar;
use Sentience\Mapper\Traits\JsonSerializesMapKeys;

class TestPayload implements JsonSerializable
{
    use JsonSerializesMapKeys;

    #[MapScalar('id')]
    public int $id;

    #[MapScalar('name')]
    public string $name;

    #[MapObject('nested_object', TestPayloadNestedObject::class)]
    public TestPayloadNestedObject $nestedObject;

    #[MapArray('nested_objects', TestPayloadNestedObject::class)]
    public array $nestedObjects;
}
