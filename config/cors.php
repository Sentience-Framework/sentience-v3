<?php

return [
    'access_control_return_origin' => env('CORS_ACCESS_CONTROL_RETURN_ORIGIN', true),
    'access_control_allow_origin' => env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN', ['*']),
    'access_control_allow_credentials' => env('CORS_ACCESS_CONTROL_ALLOW_CREDENTIALS', true),
    'access_control_allow_methods' => env('CORS_ACCESS_CONTROL_ALLOW_METHODS', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
    'access_control_allow_headers' => env('CORS_ACCESS_CONTROL_ALLOW_HEADERS', ['*'])
];
