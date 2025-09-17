<?php

return [
    'env' => env('ENV', 'production'),
    'encoding' => [
        'default' => env('DEFAULT_ENCODING', 'json'),
        'url_encoding_unique' => false
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
