<?php

namespace Sentience\Database\Queries\Enums;

enum ColumnTypeEnum: string
{
    case BOOL = 'INTEGER';
    case INT = 'BIGINT';
    case FLOAT = 'REAL';
    case STRING = 'TEXT';
    case DATETIME = 'DATETIME';

    public static function varchar(int $size = 255): string
    {
        return "VARCHAR({$size})";
    }
}
