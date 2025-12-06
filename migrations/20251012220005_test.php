<?php

use Sentience\DataLayer\Database\DB;
use Sentience\Migrations\MigrationInterface;

return new class () implements MigrationInterface {
    public function apply(DB $db): void
    {
        $db->createTable('books')
            ->ifNotExists()
            ->identity('id')
            ->string('name')
            ->int('author_id')
            ->bool('is_read', true, true)
            ->dateTime('read_at')
            ->uniqueConstraint(['name'], 'books_uniq')
            ->execute();
    }

    public function rollback(DB $db): void
    {
        $db->dropTable('books')
            ->ifExists()
            ->execute();
    }
};
