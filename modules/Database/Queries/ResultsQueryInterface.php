<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Queries\Objects\QueryWithParams;

interface ResultsQueryInterface
{
    public function toQueryWithParams(): array|QueryWithParams;
    public function toRawQuery(): string|array;
    public function execute(): mixed;
}
