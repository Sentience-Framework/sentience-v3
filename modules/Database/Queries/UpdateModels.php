<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use DateTimeInterface;
use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Enums\Chain;
use Modules\Database\Queries\Enums\Operator;
use Modules\Database\Queries\Objects\Condition;
use Modules\Database\Queries\Traits\Where;
use Modules\Models\Mapper;
use Modules\Models\Reflection\ReflectionModel;

class UpdateModels extends ModelsQueryAbstract
{
    use Where;

    protected array $updates = [];

    public function __construct(Database $database, DialectInterface $dialect, array $model)
    {
        parent::__construct($database, $dialect, $model);
    }

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $reflectionModel = new ReflectionModel($model);
            $reflectionModelProperties = $reflectionModel->getProperties();

            $values = [];
            $primaryKeyConditions = [];

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();
                $value = $model->{$property};

                $values[$column] = $value;

                if ($reflectionModelProperty->isPrimaryKey()) {
                    $primaryKeyConditions[] = new Condition(
                        Operator::EQUALS,
                        $column,
                        $value,
                        Chain::AND
                    );
                }
            }

            $queryWithParams = $this->dialect->update([
                'table' => $reflectionModel->getTable(),
                'values' => [...$values, ...$this->updates],
                'where' => [...$primaryKeyConditions, ...$this->where],
                'returning' => $reflectionModel->getColumns()
            ]);

            $results = $this->database->queryWithParams($queryWithParams);

            $updatedRow = $results->fetchAssoc();

            if ($updatedRow) {
                Mapper::mapAssoc($model, $updatedRow);

                continue;
            }
        }

        return $this->models;
    }

    public function updateColumns(array $values): static
    {
        $this->updates = array_merge($this->updates, $values);

        return $this;
    }

    public function updateColumn(string $column, null|bool|int|float|string|DateTimeInterface $value): static
    {
        $this->updates[$column] = $value;

        return $this;
    }
}
