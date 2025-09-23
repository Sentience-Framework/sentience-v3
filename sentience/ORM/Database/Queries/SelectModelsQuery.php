<?php

namespace Sentience\ORM\Database\Queries;

use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\OrderByDirectionEnum;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Traits\DistinctTrait;
use Sentience\Database\Queries\Traits\LimitTrait;
use Sentience\Database\Queries\Traits\OffsetTrait;
use Sentience\Database\Queries\Traits\OrderByTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\ORM\Database\Queries\Traits\RelationsTrait;
use Sentience\ORM\Models\Reflection\ReflectionModel;

class SelectModelsQuery extends ModelsQueryAbstract
{
    use DistinctTrait;
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    use RelationsTrait;
    use WhereTrait;

    public function __construct(Database $database, DialectInterface $dialect, string $model)
    {
        parent::__construct($database, $dialect, [$model]);
    }

    public function execute(): array
    {
        $model = $this->models[0];

        $this->validateModel($model, false);

        $reflectionModel = new ReflectionModel($model);

        $table = $reflectionModel->getTable();
        $columns = $reflectionModel->getColumns();

        $selectQuery = $this->database->select($table)
            ->columns($columns);

        if ($this->distinct) {
            $selectQuery->distinct();
        }

        $selectQuery->whereGroup(fn (): ConditionGroup => new ConditionGroup(ChainEnum::AND, $this->where));

        foreach ($this->orderBy as $orderBy) {
            $orderBy->direction == OrderByDirectionEnum::ASC
                ? $selectQuery->orderByAsc($orderBy->column)
                : $selectQuery->orderByDesc($orderBy->column);
        }

        if ($this->limit) {
            $selectQuery->limit($this->limit);
        }

        if ($this->offset) {
            $selectQuery->offset($this->offset);
        }

        $result = $selectQuery->execute();

        return array_map(
            fn (array $row): object => $this->mapAssocToModel($model, $row),
            $result->fetchAssocs()
        );
    }
}
