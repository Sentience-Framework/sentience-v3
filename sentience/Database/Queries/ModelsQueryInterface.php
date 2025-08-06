<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(): array;
}
