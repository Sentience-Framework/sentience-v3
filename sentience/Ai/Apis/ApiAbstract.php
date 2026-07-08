<?php

namespace Sentience\Ai\Apis;

use Sentience\Ai\Messages\Role;
use Sentience\Ai\Messages\SystemMessage;
use Sentience\Ai\Schema\Schemable;


abstract class ApiAbstract implements ApiInterface
{
    protected function buildStructuredOutputMessage(Schemable $schema): SystemMessage
    {
        return new SystemMessage(
            'The final response to the user should be in minified JSON. '
            . 'Follow the JSON standard (ISO/IEC 21778, ECMA-404, https://www.json.org/). '
            . 'Strictly adhere to the following schema: '
            . json_encode($schema->schema())
        );
    }
}
