<?php

namespace Sentience\DataLayer\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(bool $emulatePrepare = false): ?array;
}
