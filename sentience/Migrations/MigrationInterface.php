<?php

namespace Sentience\Migrations;

use Sentience\ORM\Database\DB;

interface MigrationInterface
{
    public function apply(DB $db): void;
    public function rollback(DB $db): void;
}
