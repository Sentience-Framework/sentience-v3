<?php

namespace sentience\Database\queries;

use sentience\Database\queries\objects\QueryWithParams;
use sentience\Database\Results;

interface QueryInterface
{
    public function build(): QueryWithParams;
    public function execute(): ?Results;
    public function tryCatch(): ?Results;
    public function toRawQuery(): string;
}
