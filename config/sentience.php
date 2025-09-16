<?php

return [
    'env' => env('ENV', 'production'),
    'requests' => [
        'unique_query_params' => false
    ],
    'responses' => [
        'default_encoding' => env('RESPONSES_DEFAULT_ENCODING', 'json')
    ],
    'errors' => [
        'catch_non_fatal' => env('ERRORS_CATCH_NON_FATAL', true),
        'stack_trace' => env('ERRORS_STACK_TRACE', false)
    ],
    'server' => [
        'host' => env('SERVER_HOST', 'localhost'),
        'port' => env('SERVER_PORT', 8000)
    ]
];
