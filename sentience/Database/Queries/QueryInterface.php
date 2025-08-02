<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Objects\QueryWithParams;
use sentience\Database\Results;

interface QueryInterface
{
    public function build(): QueryWithParams;
    public function execute(): ?Results;
    public function tryCatch(): ?Results;
    public function toRawQuery(): string;
}
