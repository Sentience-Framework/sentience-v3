<?php

use Sentience\Database\Database;
use Sentience\Database\Driver;

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
            if (!str_starts_with((string) $key, 'DB_')) {
                continue;
            }

            $option = substr($key, 3);

            $options[$option] = $value;
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
