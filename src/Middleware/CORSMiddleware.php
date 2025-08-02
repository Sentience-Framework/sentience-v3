<?php

declare(strict_types=1);

namespace src\Middleware;

use sentience\Abstracts\Middleware;
use sentience\Sentience\Request;

class CORSMiddleware extends Middleware
{
    public function __construct(protected Request $request)
    {
    }

    public function addHeaders(): void
    {
        $originHeader = $this->request->getHeader('origin');
        $originEnv = implode(', ', (array) env('ACCESS_CONTROL_ALLOW_ORIGIN', ['*']));

        $returnOrigin = env('ACCESS_CONTROL_RETURN_ORIGIN', true);

        $accessControlAllowOrigin = $returnOrigin && $originHeader ? $originHeader : $originEnv;
        $accessControlAllowCredentials = env('ACCESS_CONTROL_ALLOW_CREDENTIALS', true) ? 'true' : 'false';
        $accessControlAllowMethods = implode(', ', (array) env('ACCESS_CONTROL_ALLOW_METHODS', ['*']));
        $accessControlAllowHeaders = implode(', ', (array) env('ACCESS_CONTROL_ALLOW_HEADERS', ['*']));

        header('Access-Control-Allow-Origin: ' . $accessControlAllowOrigin);
        header('Access-Control-Allow-Credentials: ' . $accessControlAllowCredentials);
        header('Access-Control-Allow-Methods: ' . $accessControlAllowMethods);
        header('Access-Control-Allow-Headers: ' . $accessControlAllowHeaders);
    }
}
