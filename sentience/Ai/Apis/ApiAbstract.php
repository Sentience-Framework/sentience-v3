<?php

namespace Sentience\Ai\Apis;

abstract class ApiAbstract implements ApiInterface
{
    protected function buildToolsSchema(array $tools): array
    {
        $schema = [];

        foreach ($tools as $name => $toolInterface) {
            $schema[] = $toolInterface->schema($name);
        }

        return $schema;
    }
}
