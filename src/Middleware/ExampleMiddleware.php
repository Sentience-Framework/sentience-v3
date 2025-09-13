<?php

namespace Src\Middleware;

use Modules\Abstracts\Middleware;
use Modules\Sentience\Request;
use Modules\Sentience\Response;

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
