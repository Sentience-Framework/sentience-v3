<?php

namespace Sentience\Migrations;

use Sentience\DataLayer\Database\DB;

interface MigrationInterface
{
    public function apply(DB $db): void;
    public function rollback(DB $db): void;
}
