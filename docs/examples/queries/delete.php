<?php

declare(strict_types=1);

$database->delete()
    ->table('table_1')
    ->whereBetween('column2', 10, 20)
    ->orWhereNotBetween('column2', 70, 80)
    ->returning(['id'])
    ->execute();
