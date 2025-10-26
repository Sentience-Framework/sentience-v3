<?php

use Sentience\Cache\Cache;
use Sentience\Database\Driver;
use Sentience\Database\Results\ResultInterface;
use Sentience\DataLayer\Database\DB;
use Sentience\DataLayer\Database\Results\CachedResult;
use Sentience\Helpers\Log;
use Sentience\Timestamp\Timestamp;

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

        $db = DB::connect(
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
                    sprintf('Timestamp : %s', Timestamp::now()->format('Y-m-d H:i:s.u')),
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

        $queryCache = [];

        return $db->cache(
            function (string $query, ResultInterface $result) use (&$queryCache): void {
                if (!preg_match('/^SELECT/i', $query)) {
                    return;
                }

                $key = md5($query);

                $cache = Cache::getInstance();

                // print_r(serialize(CachedResult::fromInterface($result)));
                // exit;

                $cache->store(
                    $key,
                    CachedResult::fromInterface($result),
                    now()->addHours(1)
                );
            },
            function (string $query) use (&$queryCache): ?ResultInterface {
                $key = md5($query);

                $cache = Cache::getInstance();

                return $cache->retrieve($key);
            }
        );
    }
};
