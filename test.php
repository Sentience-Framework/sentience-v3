<?php

use Sentience\Timestamp\Timestamp;

include 'vendor/autoload.php';

$timestamp = Timestamp::createFromString('2025-01-01T12:10:15+05:00');

if (!$timestamp) {
    echo 'error in format';
    exit;
}

// echo $timestamp->format('Y-m-d H:i:s.u');
echo $timestamp->jsonSerialize();

