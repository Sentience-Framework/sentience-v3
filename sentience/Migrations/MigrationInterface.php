<?php

declare(strict_types=1);

namespace sentience\Migrations;

use sentience\Database\Database;

interface MigrationInterface
{
    public function apply(Database $database): void;
    public function rollback(Database $database): void;
}
