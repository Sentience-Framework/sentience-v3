<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;

interface QueryInterface
{
    public function toQueryWithParams(): array|QueryWithParams;
    public function toRawQuery(): string|array;
    public function execute(): mixed;
}
