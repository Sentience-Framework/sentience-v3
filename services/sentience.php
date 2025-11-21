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
        $driver = config('database->driver', '');
        $name = config(["database->settings->{$driver}->name", "database->settings->{$driver}->file"], '');
        $host = config("database->settings->{$driver}->host", '');
        $port = (int) config("database->settings->{$driver}->port", '');
        $unixSocket = (int) config("database->settings->{$driver}->unix_socket", null);
        $username = config("database->settings->{$driver}->username", '');
        $password = config("database->settings->{$driver}->password", '');
        $queries = config("database->settings->{$driver}->queries", []);
        $usePdo = config("database->settings->{$driver}->use_pdo", false);
        $options = config("database->settings->{$driver}", []);
        $debug = config('database->debug', false);

        $socket = !$unixSocket
            ? new NetworkSocket($host, $port, $username, $password)
            : new UnixSocket($unixSocket, $username, $password);

        $db = DB::connect(
            Driver::from($driver),
            $name,
            $socket,
            $queries,
            $options,
            $debug ? function (string $query, float $start, ?string $error = null): void {
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
            } : null,
            $usePdo
        );

        return $db;

        $queryCache = [];

        return $db->cache(
            function (string $query, CachedResult $result) use (&$queryCache): void {
                if (!preg_match('/^SELECT/i', $query)) {
                    return;
                }

                $key = md5($query);

                $cache = Cache::getInstance();

                $cache->store(
                    $key,
                    $result,
                    now()->addHours(1)
                );
            },
            function (string $query) use (&$queryCache): ?CachedResult {
                if (!preg_match('/^SELECT/i', $query)) {
                    return null;
                }

                $key = md5($query);

                $cache = Cache::getInstance();

                return $cache->retrieve($key);
            }
        );
    }
};
