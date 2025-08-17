<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(): ?array;
}
