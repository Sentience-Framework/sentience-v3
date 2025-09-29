<?php

namespace Sentience\Database\Queries\Objects;

class Having extends QueryWithParams
{
    public function __construct(
        string $query,
        array $params
    ) {
        parent::__construct($query, $params);

        $this->namedParamsToQuestionMarks();
    }
}
