<?php

namespace Modules\Database\Results;

interface ResultsInterface
{
    public function getColumns(): array;
    public function nextRowAsObject(string $class = 'stdClass'): ?object;
    public function allRowsAsObjects(string $class = 'stdClass'): array;
    public function nextRowAsAssoc(): ?array;
    public function allRowsAsAssocs(): array;
}
