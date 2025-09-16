<?php

namespace Sentience\Database\Queries;

use DateTimeInterface;
use Sentience\Database\Database;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Objects\ConditionGroupObject;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Models\Reflection\ReflectionModel;

class UpdateModelsQuery extends ModelsQueryAbstract
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

            $table = $reflectionModel->getTable();
            $columns = $reflectionModel->getColumns();

            $updateQuery = $this->database->update($table);

            $values = [];

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();
                $value = $model->{$property};

                $values[$column] = $this->value($value);

                if ($reflectionModelProperty->isPrimaryKey()) {
                    $updateQuery->whereEquals($column, $value);
                }
            }

            $updateQuery->values([...$values, ...$this->updates]);
            $updateQuery->whereGroup(fn (): ConditionGroupObject => new ConditionGroupObject(ChainEnum::AND, $this->where));
            $updateQuery->returning($columns);

            $results = $updateQuery->execute();

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
