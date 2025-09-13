<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Traits\IfExists;

class DropModel extends ModelsQueryAbstract
{
    use IfExists;

    public function __construct(Database $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, [$model]);
    }

    public function execute(): null
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $query = $this->database->dropTable($model::getTable());

        if ($this->ifExists) {
            $query->ifExists();
        }

        $query->execute();

        return null;
    }
}
