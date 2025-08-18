<?php

declare(strict_types=1);

include 'vendor/autoload.php';

$value = (float) null;

echo json_encode(get_debug_type($value));
