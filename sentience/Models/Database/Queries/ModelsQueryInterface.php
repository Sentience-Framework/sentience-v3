<?php

namespace Sentience\Models\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(): ?array;
}
