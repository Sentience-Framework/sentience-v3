<?php

use Sentience\Database\Database;
use Sentience\Database\Driver;
use Sentience\Helpers\Log;

return new class () {
    public function database(): Database
    {
        $driver = env('DB_DRIVER', '');
        $host = env('DB_HOST', '');
        $port = (int) env('DB_PORT', '');
        $name = env('DB_NAME') ?? env('DB_FILE');
        $username = env('DB_USERNAME', '');
        $password = env('DB_PASSWORD', '');
        $queries = env('DB_QUERIES', []);
        $debug = env('DB_DEBUG', false);
        $usePDO = env('DB_USE_PDO', false);

        $env = env();

        $options = [];

        foreach ($env as $key => $value) {
            if (!str_starts_with((string) $key, 'DB_')) {
                continue;
            }

            $option = substr((string) $key, 3);

            $options[$option] = $value;
        }

        return new Database(
            Driver::from($driver),
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $debug ? function (string $query, float $startTime, ?string $error = null): void {
                $endTime = microtime(true);

                $lines = [
                    sprintf('Timestamp : %s', date('Y-m-d H:i:s')),
                    sprintf('Query     : %s', $query),
                    sprintf('Time      : %.2f ms', ($endTime - $startTime) * 1000)
                ];

                if ($error) {
                    $lines[] = sprintf('Error     : %s', $error);
                }

                Log::stderrBetweenEqualSigns('Query', $lines);
            } : null,
            $options,
            $usePDO
        );
    }
};
