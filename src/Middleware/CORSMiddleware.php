<?php

declare(strict_types=1);

namespace Src\Middleware;

use Modules\Abstracts\Middleware;
use Modules\Sentience\Request;
use Modules\Sentience\Response;

class CORSMiddleware extends Middleware
{
    public const string ACCESS_CONTROL_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';
    public const string ACCESS_CONTROL_ALLOW_CREDENTIALS = 'Access-Control-Allow-Credentials';
    public const string ACCESS_CONTROL_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    public const string ACCESS_CONTROL_ALLOW_HEADERS = 'Access-Control-Allow-Headers';

    public function addHeaders(Request $request): void
    {
        $originHeader = $request->getHeader('origin');
        $originEnv = implode(', ', (array) env('ACCESS_CONTROL_ALLOW_ORIGIN', ['*']));

        $returnOrigin = env('ACCESS_CONTROL_RETURN_ORIGIN', true);

        $accessControlAllowOrigin = $returnOrigin && $originHeader ? $originHeader : $originEnv;
        $accessControlAllowCredentials = env('ACCESS_CONTROL_ALLOW_CREDENTIALS', true) ? 'true' : 'false';
        $accessControlAllowMethods = implode(', ', (array) env('ACCESS_CONTROL_ALLOW_METHODS', ['*']));
        $accessControlAllowHeaders = implode(', ', (array) env('ACCESS_CONTROL_ALLOW_HEADERS', ['*']));

        Response::header(static::ACCESS_CONTROL_ALLOW_ORIGIN, $accessControlAllowOrigin);
        Response::header(static::ACCESS_CONTROL_ALLOW_CREDENTIALS, $accessControlAllowCredentials);
        Response::header(static::ACCESS_CONTROL_ALLOW_METHODS, $accessControlAllowMethods);
        Response::header(static::ACCESS_CONTROL_ALLOW_HEADERS, $accessControlAllowHeaders);
    }
}
