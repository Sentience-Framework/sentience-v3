<?php

namespace Sentience\ORM\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(): ?array;
}
