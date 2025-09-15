<?php

namespace Sentience\Database\Queries;

use DateTimeInterface;
use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\OperatorEnum;
use Sentience\Database\Queries\Objects\ConditionObject;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Models\Reflection\ReflectionModel;

class UpdateModels extends ModelsQueryAbstract
{
    use WhereTrait;

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
                    $primaryKeyConditions[] = new ConditionObject(
                        OperatorEnum::EQUALS,
                        $column,
                        $value,
                        ChainEnum::AND
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
                $this->mapAssocToModel($model, $updatedRow);

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
