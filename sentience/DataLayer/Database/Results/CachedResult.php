<?php

namespace Sentience\DataLayer\Database\Results;

use Sentience\Database\Results\ResultAbstract;
use Sentience\Database\Results\ResultInterface;

class CachedResult extends ResultAbstract
{
    protected array $rows;

    public static function fromInterface(ResultInterface $interface): static
    {
        return new static(
            $interface->columns(),
            $interface->fetchAssocs()
        );
    }

    public function __construct(
        protected array $columns,
        array $rows
    ) {
        $this->rows = array_values($rows);
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function fetchAssoc(): ?array
    {
        $assoc = $this->rows[0] ?? null;

        if (is_null($assoc)) {
            return null;
        }

        $this->rows = array_slice($this->rows, 1);

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        return $this->rows;
    }
}
