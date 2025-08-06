<?php

declare(strict_types=1);

namespace sentience\Database\Queries;

use sentience\Database\Queries\Traits\Distinct;
use sentience\Database\Queries\Traits\GroupBy;
use sentience\Database\Queries\Traits\Having;
use sentience\Database\Queries\Traits\Limit;
use sentience\Database\Queries\Traits\Offset;
use sentience\Database\Queries\Traits\OrderBy;
use sentience\Database\Queries\Traits\Where;

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

        $select = $this->database->select($model::getTable());

        $select->columns(array_keys($model::getColumns()));

        $results = $select->execute();

        return array_map(
            fn (array $row): object => (new $model())->fromArray($row),
            $results->fetchAllAssoc()
        );
    }
}
