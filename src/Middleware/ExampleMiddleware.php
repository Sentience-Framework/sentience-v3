<?php

declare(strict_types=1);

namespace src\Middleware;

use sentience\Abstracts\Middleware;
use sentience\Sentience\Request;
use sentience\Sentience\Response;

class ExampleMiddleware extends Middleware
{
    public function killSwitch(Request $request): void
    {
        if ($request->getQueryParam('killswitch') == 'true') {
            Response::internalServerError('early termination');
        }
    }
}
