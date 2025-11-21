<?php

namespace Sentience\Cache;

use DateTimeInterface;
use Sentience\Abstracts\Singleton;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Driver;
use Sentience\DataLayer\Database\DB;
use Sentience\Helpers\Filesystem;

class Cache extends Singleton
{
    protected static function createInstance(): static
    {
        return new static(
            DB::connect(
                Driver::SQLITE,
                Filesystem::path(
                    SENTIENCE_DIR,
                    'sqlite',
                    'cache.sqlite3'
                ),
                null,
                [
                    'PRAGMA synchronous = OFF'
                ],
                [
                    AdapterInterface::OPTIONS_SQLITE_JOURNAL_MODE => 'WAL',
                    AdapterInterface::OPTIONS_SQLITE_OPTIMIZE => true,
                    AdapterInterface::OPTIONS_SQLITE_BUSY_TIMEOUT => 100
                ],
                null,
                true
            )
        );
    }

    public function __construct(protected DB $db)
    {
        $db->createTable('cache')
            ->ifNotExists()
            ->autoIncrement('id')
            ->string('key')
            ->string('data', PHP_INT_MAX)
            ->dateTime('expires_at')
            ->execute(false);
    }

    public function store(string $key, mixed $data, DateTimeInterface $expiresAt): void
    {
        $this->db->insert('cache')
            ->values([
                'key' => $key,
                'data' => base64_encode(serialize($data)),
                'expires_at' => $expiresAt
            ])
            ->execute(true);
    }

    public function retrieve(string $key): mixed
    {
        $cached = $this->db->select('cache')
            ->whereEquals('key', $key)
            ->whereGreaterThan('expires_at', now())
            ->execute(true)
            ->fetchAssoc();

        if (is_null($cached)) {
            return null;
        }

        return unserialize(base64_decode($cached['data']));
    }

    public function __destruct()
    {
        $this->db->delete('cache')
            ->whereLessThanOrEquals('expires_at', now())
            ->execute(true);
    }
}
