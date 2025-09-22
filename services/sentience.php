<?php

use Sentience\Database\Database;
use Sentience\Database\Driver;
use Sentience\Helpers\Log;

return new class () {
    public function database(): Database
    {
        $driver = config('database->driver', '');
        $host = config("database->{$driver}->host", '');
        $port = (int) config("database->{$driver}->port", '');
        $name = config(["database->{$driver}->name", "database->{$driver}->file"], '');
        $username = config("database->{$driver}->username", '');
        $password = config("database->{$driver}->password", '');
        $queries = config("database->{$driver}->queries", []);
        $usePDO = config("database->{$driver}->use_pdo", false);
        $debug = config('database->debug', false);
        $options = config("database->{$driver}", []);

        return new Database(
            Driver::from($driver),
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $debug ? function (string $query, float $start, ?string $error = null): void {
                $end = microtime(true);

                $lines = [
                    \sprintf('Timestamp : %s', date('Y-m-d H:i:s')),
                    \sprintf('Query     : %s', $query),
                    \sprintf('Time      : %.2f ms', ($end - $start) * 1000)
                ];

                if ($error) {
                    $lines[] = \sprintf('Error     : %s', $error);
                }

                Log::stderrBetweenEqualSigns('Query', $lines);
            } : null,
            $options,
            $usePDO
        );
    }
};
