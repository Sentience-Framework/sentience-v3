<?php

if (
    php_sapi_name() == 'cli-server' &&
    is_file(__DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}

require __DIR__ . '/../sentience.php';
