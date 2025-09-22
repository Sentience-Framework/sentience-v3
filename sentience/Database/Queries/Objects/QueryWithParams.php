<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Exceptions\QueryWithParamsException;

class QueryWithParams
{
    public function __construct(public string $query, public array $params = [])
    {
    }

    public function toRawQuery(DialectInterface $dialect): string
    {
        if (count($this->params) == 0) {
            return $this->query;
        }

        $params = array_map(
            fn (mixed $param): mixed => $dialect->castToQuery($param),
            $this->params
        );

        $index = 0;

        return preg_replace_callback(
            '/\?(?=(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)/',
            function () use ($params, &$index): mixed {
                if (!array_key_exists($index, $params)) {
                    throw new QueryWithParamsException('placeholder and value count do not match');
                }

                $param = $params[$index];

                $index++;

                return $param;
            },
            $this->query
        );
    }
}
