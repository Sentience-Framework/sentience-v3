<?php

use Sentience\Database\Driver;
use Sentience\DataLayer\Database\DB;
use Sentience\Helpers\Log;

return new class () {
    public function db(): DB
    {
        $driver = config('database->driver', '');
        $host = config("database->settings->{$driver}->host", '');
        $port = (int) config("database->settings->{$driver}->port", '');
        $name = config(["database->settings->{$driver}->name", "database->settings->{$driver}->file"], '');
        $username = config("database->settings->{$driver}->username", '');
        $password = config("database->settings->{$driver}->password", '');
        $queries = config("database->settings->{$driver}->queries", []);
        $usePDO = config("database->settings->{$driver}->use_pdo", false);
        $options = config("database->settings->{$driver}", []);
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
