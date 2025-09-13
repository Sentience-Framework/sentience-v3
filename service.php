<?php

use Modules\Database\Database;
use Modules\Database\Driver;

return new class () {
    public function database(): Database
    {
        $driver = env('DB_DRIVER', '');
        $host = env('DB_HOST', '');
        $port = (int) env('DB_PORT', '');
        $name = env('DB_NAME') ?? env('DB_FILE');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');
        $debug = env('DB_DEBUG', '');

        $env = env();

        $options = [];

        foreach ($env as $key => $value) {
            if (!str_starts_with($key, 'DB_')) {
                continue;
            }

            $options[$key] = $value;
        }

        return new Database(
            Driver::from($driver),
            $host,
            $port,
            $name,
            $username,
            $password,
            $debug,
            $options
        );
    }
};
