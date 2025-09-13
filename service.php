<?php

use Modules\Database\Database;
use Modules\Database\Driver;

return new class {
    public function database(): Database
    {
        $driver = env('DB_DRIVER', '');
        $host = env('DB_HOST', '');
        $port = (int) env('DB_PORT', '');
        $name = env('DB_NAME', '');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');
        $debug = env('DB_DEBUG', '');

        return new Database(
            Driver::from($driver),
            $host,
            $port,
            $username,
            $password,
            $name,
            $debug,
        );
    }
};
