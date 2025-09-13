<?php

namespace Src\Middleware;

use Sentience\Abstracts\Middleware;
use Sentience\Sentience\Request;
use Sentience\Sentience\Response;

class ExampleMiddleware extends Middleware
{
    public function killSwitch(Request $request): array
    {
        if ($request->getQueryParam('killswitch') == 'true') {
            Response::internalServerError('early termination');
        }

        return [
            'killswitch' => 'not activated'
        ];
    }
}
