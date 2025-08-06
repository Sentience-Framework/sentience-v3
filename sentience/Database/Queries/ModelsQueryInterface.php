<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(): array;
}
