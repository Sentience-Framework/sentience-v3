<?php

namespace Sentience\Database\Results;

interface ResultInterface
{
    public function columns(): array;
    public function fetchObject(string $class = 'stdClass', array $constructorArgs = []): ?object;
    public function fetchObjects(string $class = 'stdClass', array $constructorArgs = []): array;
    public function fetchAssoc(): ?array;
    public function fetchAssocs(): array;
}
