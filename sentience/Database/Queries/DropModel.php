<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Traits\IfExists;

class DropModel extends ModelsQueryAbstract
{
    use IfExists;

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
