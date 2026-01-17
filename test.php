<?php

use Sentience\Database\Database;

include 'vendor/autoload.php';

print_r(Database::getAvailableDrivers());
