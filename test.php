<?php

declare(strict_types=1);
use Modules\Helpers\Strings;

include 'vendor/autoload.php';

$value = (float) null;

echo json_encode(get_debug_type($value));

