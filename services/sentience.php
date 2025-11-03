<?php

use Sentience\Cache\Cache;
use Sentience\Database\Driver;
use Sentience\DataLayer\Database\DB;
use Sentience\DataLayer\Database\Results\CachedResult;
use Sentience\Helpers\Log;
use Sentience\Timestamp\Timestamp;

return new class () {
    public function db(): DB
    {
        $driver = Driver::from(config('database->driver', ''));
        $dsn = config("database->settings->{$driver->value}->dsn", '');
        $host = config("database->settings->{$driver->value}->host", '');
        $port = (int) config("database->settings->{$driver->value}->port", '');
        $name = config(["database->settings->{$driver->value}->name", "database->settings->{$driver->value}->file"], '');
        $username = config("database->settings->{$driver->value}->username", '');
        $password = config("database->settings->{$driver->value}->password", '');
        $queries = config("database->settings->{$driver->value}->queries", []);
        $usePdo = config("database->settings->{$driver->value}->use_pdo", false);
        $options = config("database->settings->{$driver->value}", []);
        $debug = config('database->debug', false)
            ? function (string $query, float $start, ?string $error = null): void {
                $end = microtime(true);

                $lines = [
                    sprintf('Timestamp : %s', Timestamp::now()->format('Y-m-d H:i:s.u')),
                    sprintf('Query     : %s', $query),
                    sprintf('Time      : %.2f ms', ($end - $start) * 1000)
                ];

                if ($error) {
                    $lines[] = sprintf('Error     : %s', $error);
                }

                Log::stderrBetweenEqualSigns('Query', $lines);
            }
            : null;

        $db = $driver->isSupportedBySentience()
            ? DB::connect(
                $driver,
                $host,
                $port,
                $name,
                $username,
                $password,
                $queries,
                $options,
                $debug,
                $usePdo
            ) : DB::pdo(
                    new PDO(
                        $dsn,
                        $username,
                        $password
                    ),
                    $driver,
                    $queries,
                    $options,
                    $debug
                );

        return $db;

        // return $db->cache(
        //     function (string $query, CachedResult $result): void {
        //         if (!preg_match('/^SELECT/i', $query)) {
        //             return;
        //         }

        //         $key = md5($query);

        //         $cache = Cache::getInstance();

        //         $cache->store(
        //             $key,
        //             $result,
        //             now()->addHours(1)
        //         );
        //     },
        //     function (string $query): ?CachedResult {
        //         if (!preg_match('/^SELECT/i', $query)) {
        //             return null;
        //         }

        //         $key = md5($query);

        //         $cache = Cache::getInstance();

        //         return $cache->retrieve($key);
        //     }
        // );
    }
};
