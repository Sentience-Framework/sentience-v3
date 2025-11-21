<?php

use Sentience\Cache\Cache;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;
use Sentience\Database\Sockets\UnixSocket;
use Sentience\DataLayer\Database\DB;
use Sentience\DataLayer\Database\Results\CachedResult;
use Sentience\Helpers\Log;
use Sentience\Timestamp\Timestamp;

return new class () {
    public function db(): DB
    {
        $driver = Driver::from(config('database->driver', ''));
        $dsn = config("database->settings->{$driver->value}->dsn", '');
        $name = config(["database->settings->{$driver->value}->name", "database->settings->{$driver->value}->file"], '');
        $host = config("database->settings->{$driver->value}->host", '');
        $port = (int) config("database->settings->{$driver->value}->port", '');
        $unixSocket = config("database->settings->{$driver->value}->unix_socket", null);
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
        $lazy = config('database->lazy', false);

        $socket = !$unixSocket
            ? new NetworkSocket($host, $port, $username, $password)
            : new UnixSocket($unixSocket, $username, $password);

        $supportedBySentience = in_array(
            $driver,
            [
                Driver::FIREBIRD,
                Driver::MARIADB,
                Driver::MYSQL,
                Driver::PGSQL,
                Driver::SQLITE
            ]
        );

        $db = $supportedBySentience
            ? DB::connect(
                $driver,
                $name,
                $socket,
                $queries,
                $options,
                $debug,
                $usePdo,
                $lazy
            ) : DB::pdo(
                fn (): PDO => new PDO(
                    $dsn,
                    $username,
                    $password
                ),
                $driver,
                $queries,
                $options,
                $debug,
                $lazy
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
