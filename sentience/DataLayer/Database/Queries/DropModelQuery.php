<?php

namespace Sentience\DataLayer\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\DataLayer\Models\Reflection\ReflectionModel;

class DropModelQuery extends ModelsQueryAbstract
{
    use IfExistsTrait;

    public function __construct(Database $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, [$model]);
    }

    public function execute(bool $emulatePrepare = false): null
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $reflectionModel = new ReflectionModel($model);

        $table = $reflectionModel->getTable();

        $query = $this->database->dropTable($table);

        if ($this->ifExists) {
            $query->ifExists();
        }

        $query->execute($emulatePrepare);

        return null;
    }
}
