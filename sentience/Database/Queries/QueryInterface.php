<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Results;

interface QueryInterface
{
    public function build(): array|QueryWithParams;
    public function execute(): mixed;
    public function toRawQuery(): string|array;
}
