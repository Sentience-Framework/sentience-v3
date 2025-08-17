<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use DateTimeInterface;
use Modules\Database\Queries\Traits\Where;
use Modules\Helpers\Reflector;
use Modules\Models\Attributes\Columns\AutoIncrement;
use Modules\Models\Reflection\ReflectionModel;

class UpdateModels extends ModelsQueryAbstract
{
    use Where;

    protected array $updates = [];

    public function execute(): array
    {
        foreach ($this->models as $model) {
            $this->validateModel($model);

            $query = $this->database->update($model::getTable());

            $reflectionModel = new ReflectionModel($model);
            $reflectionModelProperties = $reflectionModel->getProperties();

            $values = [];

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isInitialized($model)) {
                    continue;
                }

                $property = $reflectionModelProperty->getProperty();
                $column = $reflectionModelProperty->getColumn();

                $values[$column] = $model->{$property};
            }

            $query->values([
                ...$values,
                ...$this->updates
            ]);

            $query->returning();

            $queryWithParams = $query->toQueryWithParams();

            $results = $this->database->queryWithParams($queryWithParams);

            $updatedRow = $results->fetchAssoc();

            if ($updatedRow) {
                $model->fromDatabase($updatedRow);
            }

            $lastInsertId = $results->lastInsertId();

            if (!$lastInsertId) {
                continue;
            }

            foreach ($reflectionModelProperties as $reflectionModelProperty) {
                if (!$reflectionModelProperty->isAutoIncrement()) {
                    continue;
                }

                $model->{$reflectionModelProperty->getProperty()} = $lastInsertId;
            }
        }

        return $this->models;
    }

    public function updateColumns(array $columns): static
    {
        array_merge($this->updates, $columns);

        return $this;
    }

    public function updateColumn(string $column, null|bool|int|float|string|DateTimeInterface $value): static
    {
        $this->updates[$column] = $value;

        return $this;
    }
}
