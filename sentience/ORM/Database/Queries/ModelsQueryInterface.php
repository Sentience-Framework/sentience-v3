<?php

namespace Sentience\ORM\Database\Queries;

interface ModelsQueryInterface
{
    public function execute(bool $emulatePrepare = false): ?array;
}
