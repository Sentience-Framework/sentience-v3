<?php

declare(strict_types=1);

namespace Src\Middleware;

use Sentience\Abstracts\Middleware;
use Sentience\Sentience\Request;
use Sentience\Sentience\Response;

class ExampleMiddleware extends Middleware
{
    public function killSwitch(Request $request): void
    {
        if ($request->getQueryParam('killswitch') == 'true') {
            Response::internalServerError('early termination');
        }
    }
}
