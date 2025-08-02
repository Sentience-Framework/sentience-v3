<?php

use sentience\Environment\Environment;
use sentience\Helpers\Filesystem;
use sentience\Sentience\Sentience;

require_once __DIR__ . '/vendor/autoload.php';

define('SENTIENCE_DIR', __DIR__);

Environment::loadEnv();
Environment::loadFile(
    Filesystem::path(SENTIENCE_DIR, '.env'),
    Filesystem::path(SENTIENCE_DIR, '.env.example'),
    ['SENTIENCE_DIR' => SENTIENCE_DIR]
);

$commands = require Filesystem::path(SENTIENCE_DIR, 'commands.php');
$routes = require Filesystem::path(SENTIENCE_DIR, 'routes.php');

$sentience = new Sentience();

foreach ($commands as $command) {
    $sentience->bindCommand($command);
}

foreach ($routes as $route) {
    $sentience->bindRoute($route);
}

$sentience->execute();
