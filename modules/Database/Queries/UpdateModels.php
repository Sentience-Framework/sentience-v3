<?php

declare(strict_types=1);

namespace Modules\Database\Queries;

use DateTimeInterface;
use Modules\Database\Database;
use Modules\Database\Dialects\DialectInterface;
use Modules\Database\Queries\Traits\Where;
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
        foreach ($this->model as $model) {
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
