<?php

namespace Modules\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(): ?array;
}
