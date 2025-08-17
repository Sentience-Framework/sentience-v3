<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use Modules\Database\Queries\Traits\Distinct;
use Modules\Database\Queries\Traits\GroupBy;
use Modules\Database\Queries\Traits\Having;
use Modules\Database\Queries\Traits\Limit;
use Modules\Database\Queries\Traits\Offset;
use Modules\Database\Queries\Traits\OrderBy;
use Modules\Database\Queries\Traits\Where;

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

        $this->validateModel($model, false);

        $results = $this->database->select($model::getTable())
            ->columns($model::getColumns())
            ->execute();

        return array_map(
            fn(array $row): object => (new $model())->fromDatabase($row),
            $results->fetchAllAssoc()
        );
    }
}
