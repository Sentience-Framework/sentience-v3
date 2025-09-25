<?php

namespace Sentience\DataLayer\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(): ?array;
}
