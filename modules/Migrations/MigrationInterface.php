<?php

declare(strict_types=1);

namespace Modules\Migrations;

use Modules\Database\Database;

interface MigrationInterface
{
    public function apply(Database $database): void;
    public function rollback(Database $database): void;
}
