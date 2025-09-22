<?php

use Sentience\Config\Config;
use Sentience\Env\Env;
use Sentience\Helpers\Filesystem;
use Sentience\Sentience\Sentience;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/ini.php';

\define('SENTIENCE_DIR', __DIR__);

Env::loadEnv();
Env::loadFile(
    Filesystem::path(SENTIENCE_DIR, '.env'),
    Filesystem::path(SENTIENCE_DIR, '.env.example'),
    ['SENTIENCE_DIR' => SENTIENCE_DIR]
);

Config::loadFiles(Filesystem::path(SENTIENCE_DIR, 'config'));

$commands = require Filesystem::path(SENTIENCE_DIR, 'commands.php');
$routes = require Filesystem::path(SENTIENCE_DIR, 'routes.php');
$services = Filesystem::scandir(Filesystem::path(SENTIENCE_DIR, 'services'));

$sentience = new Sentience();

foreach ($commands as $command) {
    $sentience->bindCommand($command);
}

foreach ($routes as $route) {
    $sentience->bindRoute($route);
}

foreach ($services as $service) {
    $sentience->bindService(include $service);
}

$sentience->execute();
