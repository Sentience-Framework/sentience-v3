<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;

interface ResultQueryInterface
{
    public function toQueryWithParams(): array|QueryWithParams;
    public function toRawQuery(): string|array;
    public function execute(): mixed;
}
