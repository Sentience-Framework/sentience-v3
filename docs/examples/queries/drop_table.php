<?php

declare(strict_types=1);

$database->dropTable()
    ->ifExists()
    ->table('table_1')
    ->toRawQuery();
