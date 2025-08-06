<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\QueryWithParams;

interface ResultsQueryInterface
{
    public function toQueryWithParams(): array|QueryWithParams;
    public function toRawQuery(): string|array;
    public function execute(): mixed;
}
