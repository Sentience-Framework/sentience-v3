<?php

declare(strict_types=1);

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Traits\Distinct;
use Sentience\Database\Queries\Traits\GroupBy;
use Sentience\Database\Queries\Traits\Having;
use Sentience\Database\Queries\Traits\Limit;
use Sentience\Database\Queries\Traits\Offset;
use Sentience\Database\Queries\Traits\OrderBy;
use Sentience\Database\Queries\Traits\Where;

class SelectModels extends ModelsQueryAbstract
{
    use Distinct;
    use GroupBy;
    use Having;
    use Limit;
    use Offset;
    use OrderBy;
    use Where;

    public function execute(): array
    {
        $model = $this->models[0];

        $this->validateModel($model);

        $results = $this->database->select($model::getTable())
            ->columns(array_keys($model::getColumns()))
            ->execute();

        return array_map(
            fn(array $row): object => (new $model())->fromDatabase($row),
            $results->fetchAllAssoc()
        );
    }
}
