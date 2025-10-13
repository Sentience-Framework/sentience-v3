<?php

use Sentience\Database\Driver;
use Sentience\DataLayer\Database\DB;
use Sentience\Helpers\Log;

return new class () {
    public function db(): DB
    {
        $driver = config('database->driver', '');
        $host = config("database->{$driver}->host", '');
        $port = (int) config("database->{$driver}->port", '');
        $name = config(["database->{$driver}->name", "database->{$driver}->file"], '');
        $username = config("database->{$driver}->username", '');
        $password = config("database->{$driver}->password", '');
        $queries = config("database->{$driver}->queries", []);
        $usePDO = config("database->{$driver}->use_pdo", false);
        $options = config("database->{$driver}", []);
        $debug = config('database->debug', false);

        return DB::connect(
            Driver::from($driver),
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $options,
            $debug ? function (string $query, float $start, ?string $error = null): void {
                $end = microtime(true);

                $lines = [
                    sprintf('Timestamp : %s', date('Y-m-d H:i:s')),
                    sprintf('Query     : %s', $query),
                    sprintf('Time      : %.2f ms', ($end - $start) * 1000)
                ];

                if ($error) {
                    $lines[] = sprintf('Error     : %s', $error);
                }

                Log::stderrBetweenEqualSigns('Query', $lines);
            } : null,
            $usePDO
        );
    }
};
