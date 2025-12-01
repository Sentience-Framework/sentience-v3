<?php

namespace Sentience\Database\Queries\Objects;

use DateTimeInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\SelectQuery;

class QueryBuilder
{
    protected array $params = [];

    public function __construct(public string $sql)
    {
    }

    public function appendSql(string $sql, mixed ...$values): static
    {
        $this->sql .= sprintf($sql, ...$values);

        return $this;
    }

    public function appendSqlWithSpace(string $sql, mixed ...$values): static
    {
        $this->sql .= ' ';

        return $this->appendSql($sql, ...$values);
    }

    public function appendCommaSeparatedIdentifiers(string|array|Alias|Raw ...$identifiers): static
    {
        $this->sql .= implode(
            ', ',
            array_fill(
                0,
                count($identifiers),
                '%s'
            )
        );

        array_push($this->identifiers, ...$identifiers);

        return $this;
    }

    public function appendCommaSeparatedValues(null|int|float|string|DateTimeInterface|Identifier|SelectQuery ...$params): static
    {
        $this->sql .= implode(
            ', ',
            array_fill(
                0,
                count($params),
                '?'
            )
        );

        array_push($this->params, ...$params);

        return $this;
    }

    public function toQueryWithParams(DialectInterface $dialect): QueryWithParams
    {

    }
}
