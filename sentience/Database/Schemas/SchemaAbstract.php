<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;

abstract class SchemaAbstract implements SchemaInterface
{
    public function __construct(
        protected DatabaseInterface $database,
        protected DialectInterface $dialect
    ) {
    }
}
