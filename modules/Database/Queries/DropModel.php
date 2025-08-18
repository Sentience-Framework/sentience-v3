<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Traits\IfExists;

class DropModel extends ModelsQueryAbstract
{
    use IfExists;

    public function __construct(Database $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, $model);
    }

    public function execute(): null
    {
        $model = $this->models[0];

        $this->validateModel($model);

        $query = $this->database->dropTable($model::getTable());

        if ($this->ifExists) {
            $query->ifExists();
        }

        $query->execute();

        return null;
    }
}
