<?php

namespace Src\Middleware;

use Sentience\Abstracts\Middleware;
use Sentience\Sentience\Request;
use Sentience\Sentience\Response;

class CORSMiddleware extends Middleware
{
    public const string ACCESS_CONTROL_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';
    public const string ACCESS_CONTROL_ALLOW_CREDENTIALS = 'Access-Control-Allow-Credentials';
    public const string ACCESS_CONTROL_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    public const string ACCESS_CONTROL_ALLOW_HEADERS = 'Access-Control-Allow-Headers';

    public function headers(Request $request): void
    {
        $originHeader = $request->getHeader('origin');
        $allowedOrigins = implode(', ', (array) config('cors->access_control_allow_origin', ['*']));

        $returnOrigin = config('cors->access_control_return_origin', true);

        $accessControlAllowOrigin = $returnOrigin && $originHeader ? $originHeader : $allowedOrigins;
        $accessControlAllowCredentials = config('cors->access_control_allow_credentials', true) ? 'true' : 'false';
        $accessControlAllowMethods = implode(', ', (array) config('cors->access_control_allow_methods', ['*']));
        $accessControlAllowHeaders = implode(', ', (array) config('cors->access_control_allow_headers', ['*']));

        Response::header(static::ACCESS_CONTROL_ALLOW_ORIGIN, $accessControlAllowOrigin);
        Response::header(static::ACCESS_CONTROL_ALLOW_CREDENTIALS, $accessControlAllowCredentials);
        Response::header(static::ACCESS_CONTROL_ALLOW_METHODS, $accessControlAllowMethods);
        Response::header(static::ACCESS_CONTROL_ALLOW_HEADERS, $accessControlAllowHeaders);
    }
}
